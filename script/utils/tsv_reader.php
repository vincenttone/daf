<?php
require_once(dirname(dirname(__FILE__)).'/init_env.php');

$opt = get_args('f:', ['2json', '2sp'], [['f']], 'CMD -f $tsv_file');

$source_file = new Lib_SourceFile($opt['f']);
foreach ($source_file as $_k => $_s) {
    if (isset($opt['2json'])) {
        echo json_encode($_s['data']);
        echo PHP_EOL;
    } elseif (isset($opt['2sp'])) {
        echo serialize($_s['data']);
        echo PHP_EOL;
    } else {
        p($_s);
    }
}
if (!isset($opt['2json']) && !isset($opt['2sp'])) {
    p($source_file->current_line_no());
}