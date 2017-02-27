# -*- coding:utf-8 -*-
import ConfigParser, os
import helper
from helper import singleton

@singleton
class DaConfig:
    RUN_MODE_PRO = 1
    RUN_MODE_DEV = 2

    app_conf = None
    run_mode = 1
    home_path = helper.get_upper_dir(__file__, 3)
    conf_path = os.path.abspath(home_path + '/conf')
    log_path = os.path.abspath(home_path + '/log')
    db_conf = {}

    def __init__(self):
        self.app_conf = self.get_conf('app')
        run_mode = self.app_conf['base']['run_mode']
        if (run_mode == 'DA_RUN_MODE_PRO'):
            self.run_mode = self.RUN_MODE_PRO
        else:
            self.run_mode = self.RUN_MODE_DEV
        log_dict = self.get_conf('log', 'base')
        self.log_path = os.path.abspath(self.home_path + '/' + log_dict['path'])

    def get_conf(self, path_name, section = None, option = None):
        def process_items_result(data):
            result = {}
            for i, v in data:
                result[i] = v
            return result
        config_parser = ConfigParser.ConfigParser()
        conf_dir = self.conf_path+'/'+ path_name +'.ini'
        dev_conf_dir = self.conf_path+'/'+ path_name +'.dev.ini'
        if (self.run_mode == 2 and os.path.exists(dev_conf_dir)):
            conf_dir = dev_conf_dir
        read = config_parser.read(conf_dir)
        if not read:
            return None
        if section is None:
            sections = config_parser.sections()
            result = {}
            for i in sections:
                r = config_parser.items(i)
                r = process_items_result(r)
                result[i] = r
            return result
        if option is None:
            if config_parser.has_section(section):
                r = config_parser.items(section)
                return process_items_result(r)
            else:
                return None
        else:
            if config_parser.has_option(section, option):
                return config_parser.get(section, option)
            else:
                return None

    def get_db_conf(self, section):
        if section in self.db_conf:
            return self.db_conf[section]
        else:
            db_point = self.get_conf('db', section)
            if db_point is None:
                return None
            db_path = db_point['path']
            config = self.get_conf(db_path)
            if config is None:
                piece = db_path.split('/')
                p_len = len(piece)
                path = '/'.join(piece[0:p_len-1])
                section = piece[-1]
                config = self.get_conf(path, section)
                if config is not None:
                    self.db_conf[section] = config
                else:
                    return None
            if db_point.has_key('db'):
                config['db'] = db_point['db']
            return config

if __name__ == '__main__':
    c = DaConfig()
    print c.get_conf('log')
    print c.get_db_conf('task')
    print c.get_db_conf('s_data')
    print c.get_db_conf('p_data')
    print c.home_path
    print c.conf_path
    print c.log_path
    print c.app_conf
    print c.run_mode
