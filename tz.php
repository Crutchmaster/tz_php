<?php
function out(...$strings) { echo implode(" ",$strings)."\n"; } //вывод всех аргументов в stdout с переносом

function array_every($array, $callback) { //проверка, что каждый элемент массива удовлетворяет условиям в функции $callback
  if (!is_array($array)) return false;
  foreach ($array as $v) {
    if (!$callback($v)) return false;
  }
  return true;
}

function checkInputGraph($arr) { //проверка входящего графа - массив массивов с целыми
if (!is_array($arr) ||
    !array_every($arr,
      function($value) {
        return array_every($value,
          function($num) { return is_integer($num); });
      })) {
    out("Graph must be array of arrays like [[1,2],[1,3],[2,3]]");
    exit();
    }
}
//Преобразование минималистического представления двунаправленного графа
//На выходе будет массив, каждый индекс элемента которого будет соответствовать вершине графа, а каждый элемент содержать массив ребер, исходящих из соотвествующей вершины
function makeGraph($graphLinks) {
  $nodes = max(array_merge([], ...$graphLinks)); //Число вершин = максимальному номеру вершины
  $links = array_fill(0, $nodes + 1, array());
  foreach ($graphLinks as $arr) { //граф получается двунаправленный, это важно при его обходе
    [$src, $dest] = $arr;
    array_push($links[$src], $dest);
    array_push($links[$dest], $src);
  }
  return [$nodes, $links];
}
//Класс узла, создаваемого при проходе графа. Нужен для получеия обратного пути
class PathNode {
  public ?PathNode $src = null;
  public int $nodeId;
  function __construct($src, $nodeId) {
    $this->src = $src;
    $this->nodeId = $nodeId;
  }
}

//Функция поиска в ширину
function pathfindBFS($links, $nodes, $from, $to) : array {
  $checked = []; //посещенные (сгоревшие) узлы (можно было использовать Set, но были какие-то проблемы с доустановкой чего-то, т.к. могут появиться проблемы с запуском в другом месте, оставил массив)
  //спецслучаи: одинокий узел, неверные индексы, одинаковые индексы
  if (!count($links[$from]) || $from < 0 || $from >= $nodes) {
    return [ "len" => 0, "path" => null ];
  }
  if ($from == $to) {
    return ["len" => 0, path => [] ];
  }
  $curNode = new PathNode(null, $from); //начальный узел
  $stack = []; //массив узлов, которые надо обойти

  while ($curNode && $curNode->nodeId != $to) { //пока еще есть куда идти и не дошли до цели
    foreach ($links[$curNode->nodeId] as $target) {
      if (!in_array($target, $checked)) { //все соседние, кроме тех, что уже обошли
        $node = new PathNode($curNode, $target); //создаём сделующий узел, отмечаем откуда мы пришли
        array_push($checked, $target); //отмечаем в уже посещенных
        array_push($stack, $node); //добавляем его в очередь на обход
      }
    }
    $curNode = array_pop($stack); //обходим следующий узел в очереди
  }
  //узли закончились, целевой узел не найден
  if ($curNode === null) return [ "len" => 0, "path" => null ];
  //восстанавливаем путь
  $path = [];
  while ($curNode->src) { //пока не перейдём на начальный узел
    array_push($path, $curNode->nodeId); //добавляем в путь его номер
    $curNode = $curNode->src; //переход
  }
  array_push($path, $from); //начало пути
  return [ "len" => count($path) - 1, "path" => array_reverse($path) ]; //разворачиваем путь
}

//Получение аргументов
//Запуск: php tz.php graph.json 1 6 > log.txt
//graph.json - файл с графом
//1 - исходная точка
//6 - целевая точка
//log.txt - файл лога
//
//Входной формат: массив из массивов, в каждом из которых по два элемента - номера связанных вершин
//Например [[1,2],[2,3],[3,1]]
[,$graphFile, $from, $to] = $argv;
$from = intval($from);
$to = intval($to);
$graphLinks = json_decode(file_get_contents("graph.json")); //чтение графа
checkInputGraph($graphLinks); //проверка графа
[$nodes, $links] = makeGraph($graphLinks); //перобразование графа из входного формата во внутренний
["len" => $len, "path" => $path] = pathfindBFS($links, $nodes, $from, $to); //поиск пути
//текствый вывод
$source = -1;
out("Graph:");
foreach ($links as $link) { //связи - вершина графа - ребра. Граф двунаправленный.
  $source++;
  if (!count($link)) continue;
  echo $source." ";
  foreach ($link as $target) {
    echo "->".$target." ";
  }
  echo "\n";
}
//вывод пути - длинна и сам путь из вершин
out("Path length:", $len);
out("Path:", implode("->", $path));
?>
