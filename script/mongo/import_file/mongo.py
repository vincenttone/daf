# -*- coding:utf-8 -*-
import pymongo
from config import DaConfig

class DaMongo:
    host = None
    port = None
    current_db_name = None
    current_table_name = None
    db = None
    collection = None
    mongo_client = None

    def __init__(self, host, port, db = None):
        self.host = host
        self.port = int(port)
        self.connect()
        (db is None) or self.set_db_name(db)

    def __del__(self):
        self.disconnect()

    def set_db_name(self, db_name):
        self.current_db_name = db_name
        self.db = self.mongo_client[self.current_db_name]
        return self

    def set_table_name(self, table_name):
        self.current_table_name = table_name
        self.collection = self.db[table_name]
        return self

    def check_alive(self):
        alive = self.mongo_client.alive()
        if not alive:
            self.connect()
        return self

    def bulk_save(self, data):
        bulk = self.collection.initialize_unordered_bulk_op()
        for d in data:
            bulk.find({'_id': d['_id']}).upsert().replace_one(d)
        result = bulk.execute()
        return result

    def connect(self):
        self.mongo_client = pymongo.MongoClient(self.host, self.port)
        return self

    def disconnect(self):
        if self.mongo_client is not None:
            self.mongo_client.close()
            self.mongo_client = None
        return self

    @staticmethod
    def get_conf(conf_name):
        c = DaConfig()
        conf = c.get_db_conf(conf_name)
        return conf
