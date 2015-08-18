# -*- coding:utf-8 -*-
import os
from da_log import Log

class SourceFile:
    separator = "\t"
    file_path = None
    file_handler = None
    has_title = True
    title = None
    title_len = 0
    current_count = 0

    def __init__(self, file_path):
        self.file_path = file_path
        if os.path.exists(file_path):
            self.file_handler = open(file_path, 'r')
            self.init_title()
        else:
            self.file_handler = None

    def __enter__(self):
        if self.file_handler is None:
            return None
        return self

    def __exit__(self, type, value, trace):
        if self.file_handler is None:
            return None
        self.file_handler.close()
        self.file_handler = None

    def init_title(self):
        if self.has_title:
            self.title = self.get_line()
            self.title_len = len(self.title)
        return self

    def get_line(self):
        line = self.file_handler.readline()
        line = line.strip()
        return line.split(self.separator)

    def read(self):
        for line in self.file_handler:
            self.current_count += 1
            line = line.strip()
            line_data = line.split(self.separator)
            if self.has_title:
                line_len = len(line_data)
                if self.title_len != line_len:
                    Log.warn("Line count wrong: Title: [%s] Data: [%s]" % (','.join(self.title), ','.join(line_data)))
                    continue
                line_data = dict(zip(self.title, line_data))
            yield line_data

def get_source_file(file_path):
    return SourceFile(file_path)

if __name__ == '__main__':
    with get_source_file('./test.rs') as file:
        #file = SourceFile('./test.rs')
        for i in file.read():
            print(i)
        print(file.current_count)
    with get_source_file('xxx.xxx') as file:
        if file is not None:
            for i in file.read():
                print(i)
            print(file.current_count)
