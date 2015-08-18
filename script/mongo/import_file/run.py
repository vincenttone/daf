# -*- coding:utf-8 -*-
import os, datetime, time, sys
import snappy, bson
from mongo_factory import MongoFactory
from source_reader import get_source_file
from file_scaner import Scaner
from config import DaConfig
from da_log import Log

class DataImport:
    KEY_DB = 'db'
    KEY_ID = '_id'
    KEY_TABLE = 'table'
    KEY_TMP_TABLE = 'table_name'
    KEY_FILES = 'files'
    KEY_CONTENT = 'content'

    SAVE_LIMIT = 200
    SUFFIX_LIST = [
        '.data', '.db_task', '.fail_data',
        '.field_diff', '.poichk_data',
        '.ver_data', '.src_data', '.card_data',
        '.res',
    ]
    INT_FILED_LIST = [
        'ap_id', 'src_id', 'status', 'errno',
        'cardid', 'ts', 'ct', 'ut', 'task_id',
        'check_status', 'check_info'
    ];

    data_to_save = {}
    counter = {}
    time_begin = None
    time_end = None

    def __init__(self):
        self.time_begin = datetime.datetime.now()

    def scan(self, conf = 'scaner/data_source'):
        '''
        scan files
        save files
        mv files
        '''
        c = DaConfig()
        config = c.get_conf(conf)
        s = Scaner([], None, self.SUFFIX_LIST)
        for (k, v) in config.items():
            db = v['db']
            file_dir = v['dir']
            out_dir = v['out_dir']
            if not os.path.isdir(file_dir): continue
            s.set_scan_dirs(file_dir)
            files = s.scan().files
            len(files) == 0 or self.import_data(db, files, file_dir, out_dir)
            s.clean_up()
        return self

    def import_data(self, db, files, file_dir, out_dir):
        '''
        import data to mongo
        '''
        Log.info("IMPORT-FILE prepare save %r to %s" %(files, db))
        # begin save datas to db
        for f in files:
            with get_source_file(f) as source:
                Log.info("IMPORT-FILE begin process file [%s]" % f)
                if source is None:
                    Log.warn("IMPORT-FILE no such file [%s]" % f)
                    continue
                for data in source.read():
                    # get table field for save
                    table, data = self.get_save_table(data)
                    if table is None:
                        Log.warn(
                            "File [%s] has no [%s] or [%s] field, data: %r" \
                            %(f, self.KEY_TABLE, self.KEY_TMP_TABLE, data)
                        )
                        continue
                    data = self.convert_data(data)
                    if data is None:
                        continue
                    # get data id and save data each self.SAVE_LIMIT
                    data_id = data[self.KEY_ID]
                    if self.data_to_save.has_key(table):
                        self.data_to_save[table].append(data)
                    else:
                        self.data_to_save[table] = [data]
                    # global counter
                    if self.counter.has_key(table):
                        self.counter[table] += 1
                    else:
                        self.counter[table] = 1
                    # up to self.SAVE_LIMIT, save data
                    if self.counter[table] % self.SAVE_LIMIT == 0:
                        save_result = self.save(db, table, self.data_to_save[table])
                        if save_result is not None:
                            self.show_save_result(db, table, save_result)
                        else:
                            log.warn("save result is None! file: [%s] db: [%s], table: [%s], data: %r" %(f, db, table, self.data_to_save[table]))
                        self.data_to_save[table] = []
            # save left datas to db
            if len(self.data_to_save) > 0:
                for (table, data) in self.data_to_save.items():
                    if len(data) == 0: continue
                    save_result = self.save(db, table, data)
                    if save_result is not None:
                        self.show_save_result(db, table, save_result)
                    self.data_to_save[table] = []
            self.mv_files(db, f, file_dir, out_dir)

    def convert_data(self, data):
        # get content and compress
        if data.has_key(self.KEY_CONTENT):
            unc_text = data[self.KEY_CONTENT].encode('utf-8')
            compressed = snappy.compress(unc_text)
            compressed = bson.binary.Binary(compressed)
            data[self.KEY_CONTENT] = compressed
        if data is None:
            Log.warn("Data is none..., file: %s" % f)
            return None
        # convert int field from string to int
        for int_field in self.INT_FILED_LIST:
            try:
                if data.has_key(int_field):
                    data[int_field] = int(float(data[int_field]))
            except:
                Log.warn("Data field convert int failed, data: %r" % data)
                data = None
        return data

    def get_save_table(self, data):
        table = None
        if data.has_key(self.KEY_TABLE):
            table = data[self.KEY_TABLE]
            del data[self.KEY_TABLE]
        elif data.has_key(self.KEY_TMP_TABLE):
            table = data[self.KEY_TMP_TABLE]
            del data[self.KEY_TMP_TABLE]
        return (table, data)

    def save(self, db, table, data):
        mongo_factory = MongoFactory()
        mongo = mongo_factory.get_mongo(db)
        if mongo is None:
            Log.error("get none as mongo, please check config of db :"+db+", table:"+table)
            return None
        save_for_log = "MONGO-INFO start saving data to [%s:%s] [%s.%s]" %(mongo.host, mongo.port, db, table)
        Log.info(save_for_log)
        if len(data) == 0:
            return None
        else:
            return mongo.check_alive().set_table_name(table).bulk_save(data)

    @staticmethod
    def show_save_result(db, table, save_result):
        '''
        log save result
        Save result as below:
        {'nUpserted': 0, 'nMatched': 200, 'upserted': [{_index: INDEX, _id: ID}], 'writeConcernErrors': [], 'nInserted': 0, 'nRemoved': 0, 'writeErrors': []}
        '''
        log = "SAVE-INTO [%s.%s] " %(db, table)
        if save_result.has_key('nUpserted'):
            log += "UPSERT-COUNT: [%d], " % save_result['nUpserted']
            del(save_result['nUpserted'])
        if save_result.has_key('nMatched'):
            log += "SAVE-COUNT: [%d], " % save_result['nMatched']
            del(save_result['nMatched'])
        if save_result.has_key('upserted') and save_result['upserted']:
            log += "UPSERT: "
            for i in save_result['upserted']:
                log += i['_id'] + ', '
            del(save_result['upserted'])
        for x, y in save_result.items():
            log += "%s: %r " %(x, y)
        Log.info(log)

    def mv_files(self, db, filepath, from_dir, out_dir):
        if len(filepath) == 0:
            Log.error("MV-FILE empty filename, db: [%s], file: [%s]" %(db, filepath))
            return False
        child_path = filepath.replace(from_dir, '')
        to_path = out_dir + child_path
        to_dir = os.path.dirname(to_path)
        if not os.path.exists(to_dir):
            os.makedirs(to_dir, 0755)
        Log.info("MV-FILE from [%s] to [%s]" %(filepath, to_path))
        mv_files = os.renames(filepath, to_path)
        return mv_files


    def __enter__(self):
        return self

    def __exit__(self, type, value, trace):
        self.time_end = datetime.datetime.now()
        table_count = '\n'.join("SAVE-INFO: %s: %d" % (table, count) for table, count in self.counter.items())
        self.counter.clear()
        if table_count.strip() != '':
            Log.info("Begin at [" + self.time_begin.strftime("%Y-%m-%d %H:%M:%S") + "]")
            Log.info(table_count)
            Log.info("End at [" + self.time_end.strftime("%Y-%m-%d %H:%M:%S") + "]")

def import_data():
    return DataImport()

if __name__ == '__main__':
    start_info = "Start at [%s]" % datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    Log.info(start_info)
    while True:
        with import_data() as di:
            di.scan()
            sys.stdout.flush()
            time.sleep(1)
