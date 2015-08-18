# -*- coding:utf-8 -*-
import os
from helper import singleton
from config import DaConfig
from datetime import datetime
import logging, logging.handlers

class Log:
    LEVEL = logging.INFO
    FORMAT = '--%(levelname)s-- [%(asctime)s] [%(process)d] %(message)s'
    LOG_PREFIX = 'async_import'
    LOG_SUFFIX = 'log'

    __log_handler = None
    log_dir = None

    @staticmethod
    def __get_log_file_name():
        c = DaConfig()
        log_file = "%s/%s.%s" % (c.log_path, Log.LOG_PREFIX, Log.LOG_SUFFIX)
        log_dir = os.path.dirname(log_file)
        os.path.exists(log_dir) or os.mkdirs(log_dir, 0755)
        return log_file

    @staticmethod
    def get_log():
        if Log.__log_handler is None:
            #logging.basicConfig()
            log = logging.getLogger('')
            log.setLevel(Log.LEVEL)
            formater = logging.Formatter(Log.FORMAT)
            handler = logging.handlers.TimedRotatingFileHandler(Log.__get_log_file_name(), "H", 1, 0)
            handler.suffix = "%Y%m%d.%H"
            handler.setFormatter(formater)
            log.addHandler(handler)
            Log.__log_handler = log
        return Log.__log_handler

    @staticmethod
    def debug(info):
        log = Log.get_log()
        return log.debug(info)

    @staticmethod
    def info(info):
        log = Log.get_log()
        return log.info(info)

    @staticmethod
    def warn(info):
        log = Log.get_log()
        return log.warning(info)

    @staticmethod
    def error(info):
        log = Log.get_log()
        return log.error(info)

if __name__ == '__main__':
    log = Log()
    Log.debug('this is a test log')
    Log.info('this is a info log')
    Log.warn('this is a warning log')
    Log.error('this is a error log')
