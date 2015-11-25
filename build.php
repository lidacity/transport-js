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

// сжать JS
compileScript($js);

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
    '_TEMPLATE_' => $tpl,
    '_STOPS_' => $sample['stops'],
    '_ROUTES_' => $sample['routes'],
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
    '_TEMPLATE_' => $tpl,
    '_STOPS_' => '<?= $stops ?>',
    '_ROUTES_' => '<?= $routes ?>',
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
 * @param $tpl string код шаблона
 */
function extractTemplate(&$html, &$tpl) {
    $pattern = '|<!-- TEMPLATE(.+)TEMPLATE -->|ms';
    if (preg_match($pattern, $html, $matches)) {
        $s = str_replace(PHP_EOL, '', $matches[1]);
        $s = str_replace('    ', '', $s);
        $s = addslashes($s); // после сжатия JS шаблон будет в двойных кавычках
        $tpl = trim($s);
        $html = preg_replace($pattern, '', $html);
    }
}

/**
 * Сжатие JS-кода
 * Применяется онлайн-сервис Google Closure Compiler
 *
 * @see https://developers.google.com/closure/compiler/docs/api-tutorial1
 *
 * @param $js string
 */
function compileScript(&$js) {
    $post['js_code'] = $js;
    //$post['compilation_level'] = 'WHITESPACE_ONLY';
    $post['compilation_level'] = 'SIMPLE_OPTIMIZATIONS';
    //$post['compilation_level'] = 'ADVANCED_OPTIMIZATIONS';
    $post['output_info'] = 'compiled_code';
    $post['output_format'] = 'text';

    // применяется cURL (Client URL Library)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, 'http://closure-compiler.appspot.com/compile');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

    // обращение к сервису
    $result = curl_exec($ch);
    if ($result === false) {
        echo 'JS compilation error: ' . curl_error($ch) . PHP_EOL;
    } else {
        $js = $result;
    }

    // освобождение ресурсов
    curl_close($ch);
}
