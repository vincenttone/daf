# -*- coding:utf-8 -*-
from mongo import DaMongo
from helper import singleton
from da_log import Log

@singleton
class MongoFactory:
    __mongos = {}

    def get_mongo(self, conf_path):
        conf = DaMongo.get_conf(conf_path)
        if conf is None:
            Log.error('MongoFactory no such conf_path [' + conf_path +']')
            return None
        host = conf['host']
        port = conf['port']
        db = conf['db']
        key = host + port
        if self.__mongos.has_key(key):
            return self.__mongos[key].set_db_name(conf['db'])
        else:
            mongo = DaMongo(conf['host'], conf['port'], conf['db'])
            self.__mongos[key] = mongo
            return mongo

if __name__ == '__main__':
    f = MongoFactory()
    print f.get_mongo('aoooo')
