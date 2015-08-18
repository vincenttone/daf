# -*- coding:utf-8 -*-
import os

def singleton(klass, *args, **kw):
    ''' singleton helper'''
    instances = {}
    def _wraper():
        if klass not in instances:
            instances[klass] = klass(*args, **kw)
        return instances[klass]
    return _wraper

def get_upper_dir(path_or_file, level = 0):
    ''' get the dir up of the file or dir'''
    path = os.path.abspath(path_or_file)
    while level >= 0:
        path = os.path.dirname(path)
        level -= 1
    return path

if __name__ == '__main__':
    print get_upper_dir(__file__, 3)
