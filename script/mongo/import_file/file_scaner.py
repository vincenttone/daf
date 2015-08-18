# -*- coding:utf-8 -*-
import os, types
from da_log import Log

class Scaner:
    file_prefix = None
    file_suffix = None
    loop_limit = 5

    dirs = set([])
    files = []

    def __init__(self, dirs = [], file_prefix = None, file_suffix=None):
        self.set_scan_dirs(dirs)
        self.set_file_prefix(file_prefix)
        self.set_file_suffix(file_suffix)

    def set_scan_dirs(self, dirs = []):
        self.dirs = set([])
        if type(dirs) is types.StringType:
            self.dirs.add(os.path.abspath(dirs))
        elif type(dirs) is types.ListType:
            for dir in dirs:
                self.dirs.add(os.path.abspath(dir))
        return self

    def append_scan_dir(self, dir):
        self.dirs.add(dir)
        return self

    def clean_up(self):
        self.dirs = set([])
        self.files = []
        return self

    def set_file_prefix(self, prefix = None):
        self.file_prefix = prefix

    def set_file_suffix(self, suffix = None):
        self.file_suffix = suffix

    def scan(self):
        for dir in self.dirs:
            files = Scaner.scan_files(dir, self.file_prefix, self.file_suffix, self.loop_limit)
            self.files = self.files + files
        return self

    @staticmethod
    def scan_files(dir, file_prefix = None, file_suffix = None, loop_limit = 5):
        all_files = []
        if not os.path.isdir(dir):
            Log.debug("SCAN-DIR dir [%s] is not exists or not a dir" % dir)
            return []
        files = os.listdir(dir)
        for file in files:
            loop_limit_now = loop_limit
            f = dir+'/' +file
            if os.path.isdir(f):
                if loop_limit_now <= 0: return []
                sub_files = Scaner.scan_files(f, file_prefix, file_suffix, loop_limit_now - 1)
                all_files = all_files + sub_files
            elif os.path.isfile(f):
                if (file_prefix is not None) and (file.find(file_prefix) != 0):
                    continue
                if file_suffix is not None:
                    if type(file_suffix) is types.StringType:
                        if not Scaner.has_suffix(file, file_suffix):
                            continue
                    else:
                        if not Scaner.has_suffixes(file, file_suffix):
                            continue
                tmp_files = []
                tmp_files.append([f, os.stat(f).st_mtime])
                tmp_files = sorted(tmp_files, key = lambda f: f[1])
                for i in tmp_files: all_files.append(i[0])
        return all_files

    @staticmethod
    def has_suffix(filename, suffix):
        suffix_len = len(suffix)
        if (filename[-suffix_len:len(filename)]) == suffix:
            return True
        else:
            return False

    @staticmethod
    def has_suffixes(filename, suffixes):
        for suffix in suffixes:
            if Scaner.has_suffix(filename, suffix):
                return True;
        return False;

if __name__ == "__main__":
    w = Scaner(['../'], None, ['pyc', 'py'])
    files = w.scan().files
    for i in files:
        print i
