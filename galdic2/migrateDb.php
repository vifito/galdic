<!doctype html>
<html lang="gl-ES">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>
<?php
$dsn = 'mysql:host=localhost;dbname=galdic2';
$username = 'root';
$password = 'root';
$options = array(
    //PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

$db = new PDO($dsn, $username, $password, $options);
$query = 'SELECT * FROM terms INNER JOIN definitions ON terms.id = definitions.term_id
  ORDER BY terms.name, definitions.term_id';
$rows = $db->query($query);

$mongo  = new MongoClient();
$galdic = $mongo->galdic; // db
$terms  = $galdic->terms; // collection

$current = -1;
$entries = [];
while($row = $rows->fetch(PDO::FETCH_ASSOC)) {

    if(!isset($entries[$row['name']])) {
        $entries[$row['name']] = [];
    }

    $entries[$row['name']]['name'] = $row['name'];

    //$entries[$row['name']]['created']  = new \DateTime($row['created']);
    $entries[$row['name']]['created']  = new MongoDate(strtotime($row['created']));

    //$entries[$row['name']]['modified'] = new \DateTime($row['modified']);
    $entries[$row['name']]['modified']  = new MongoDate(strtotime($row['updated']));

    $entries[$row['name']]['author']   = 'auto';
    $entries[$row['name']]['hits']     = 0;

    if(isset($row['pronuntiation']) && !empty($row['pronuntiation'])) {
        $entries[$row['name']]['pronuntiation'] = $row['pronuntiation'];
    }

    $content = preg_replace('/[\n\t\r]/', '', $row['content']);
    $content = strip_tags($content, '<a><i><strong><em><b>');
    $content = preg_replace('/\s+/', ' ', trim($content));

    $entries[$row['name']]['definitions'][] = $content;

    echo '<p>' . $row['name'] . ': ' . $content . '</p>';
}


foreach($entries as $entry) {
    $terms->update(['name' => $entry['name']], $entry, ['upsert' => 1]);
}

$mongo->close(true);
?>
</body>
</html>