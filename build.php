<?php
/**
 * Сборка HTML + CSS + JS в единый документ
 * 2 варианта документа, см. каталог /result
 * * test.html - с тестовыми данными
 * * _html.php - шаблон для конструктора расписания (Yii2)
 */

$sample = include 'data/sample.php';

// значок в виде data URL
$icon = 'data:image/png;base64,' . base64_encode(file_get_contents('data/icon.png'));

// CSS взять без изменений
$css = file_get_contents('src/transport.css');

// в JS надо вставить данные и шаблон блока маршрута
$js = file_get_contents('src/transport.js');

// HTML для начала обработки, в него надо подставить CSS и JS, потом все остальное
$html = file_get_contents('src/transport.html');

// из HTML выделить код шаблона в отдельную строку
extractTemplate($html, $tpl);

// замена для тестового документа
$replace['test'] = [
    // для HTML
    '<!-- CSS -->' => $css,
    '<!-- JS -->' => $js,
    '<!-- TITLE -->' => $sample['title'],
    '<!-- AUTHOR -->' => $sample['author'],
    '<!-- VERSION -->' => $sample['version'],
    '<!-- HELP -->' => nl2br($sample['help'], true),
    '<!-- ICON -->' => $icon,
    // для JS
    '(TEMPLATE)' => $tpl,
    '(STOPS)' => $sample['stops'],
    '(ROUTES)' => $sample['routes'],
];

// замена для шаблона PHP (Yii2)
$replace['php'] = [
    // для HTML
    '<!-- CSS -->' => $css,
    '<!-- JS -->' => $js,
    '<!-- TITLE -->' => '<?= $description ?>',
    '<!-- AUTHOR -->' => '<?= $vendor ?>',
    '<!-- VERSION -->' => '<?= $version ?>',
    '<!-- HELP -->' => '<?= $help ?>',
    '<!-- ICON -->' => '<?= $icon ?>',
    // для JS
    '(TEMPLATE)' => $tpl,
    '(STOPS)' => '<?= $stops ?>',
    '(ROUTES)' => '<?= $routes ?>',
];

// файлы результатов
$files = [
    'test' => 'test.html',
    'php' => '_html.php',
];
foreach ($files as $key => $file) {
    $s = str_replace(array_keys($replace[$key]), array_values($replace[$key]), $html);
    file_put_contents('result/' . $file, $s);
}

/**
 * HTML-код шаблона блока маршрута задается как комментарий в HTML-файле
 * Выделить этот код, чтобы позже подставить в JS в виде строки
 * Удалить код, т.к. он более не нужен в HTML-файле
 *
 * @param $html string
 * @param $tpl string код 
 */
function extractTemplate(&$html, &$tpl) {
    $pattern = '|<!-- TEMPLATE(.+)TEMPLATE -->|ms';
    if (preg_match($pattern, $html, $matches)) {
        $s = str_replace(PHP_EOL, '', $matches[1]);
        $s = str_replace('    ', '', $s);
        $tpl = trim($s);
        $html = preg_replace($pattern, '', $html);
    }
}
