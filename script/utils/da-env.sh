#!/bin/sh
function da-gen()
{
    php $da_path/script/utils/de_gen_data.php $@
}

function da-cd()
{
    cd $da_path
}

function da-log-awk-data()
{
    awk -F'data: ' '{print $2}'
}

function da-log-data()
{
    da-log-awk-data|da-gen $@
}

function da-current-log()
{
    if [ $# -lt 1 ]; then
        echo 'CMD needle'
    else
        echo $da_path\/log\/data-access\.$1\.`date +%Y%m%d%H`\.log
    fi
}

function da-grep-log()
{
    grep --color=always $2 `da-current-log $1`
}

function da-log-watcher
{
    if [ $# -lt 1 ]; then
        echo 'use as CURRENT_SCRIPT log_type'
    else
        if [ ! -z $da_path ];then
            dir=$da_path
            if [ ${dir:((${#dir} - 1))} = '/' ]; then
                dir=${dir:0:((${#dir} - 1))}
            fi
            type=$1
            log_dir=$dir\/log\/data-access\.$type\.`date +%Y%m%d%H`\.log
            until [ -f $log_dir ]; do
                echo 'DIR ['$log_dir'] not exists, keep watching'
                sleep 1
            done
            echo ------------ Begin watching [$log_dir] -------------------
            echo 
            tail -f $log_dir
        else
            echo 'Please set shell var: $da_path for data access home dir'
        fi
    fi
}

function da-run-ap
{
    php $da_path/script/tools/access_point.php --meta --run $@
}

function da-start-bps
{
    nohup php $da_path/script/tools/bps.php --start &
}

function da-row-num
{
    awk -F "\t" '{for (i=1;i<NF;i++){if ($i == "'$1'"){print i}}}';
}
