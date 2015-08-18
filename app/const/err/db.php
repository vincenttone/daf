<?php
/**
 * @file dberr.php
 * @author vincent
 * @date 2013-12-8
 * @description
 *  数据库相关命令号类
 **/

class Const_Err_Db{
	// 数据同步错误号区间 200 ~ 299
	const ERR_CONNECT_FAIL = 200;	// 数据库连接错误
	const ERR_INSERT_FAIL = 201; 	// 数据库insert失败
	const ERR_UPDATE_FAIL = 202; 	// 数据库update失败
	const ERR_SELECT_FAIL = 203; 	// 数据库select失败
    const ERR_GET_DATA_FAIL = 204;
    const ERR_SAVE_DATA_FAIL = 205;

    const ERR_MONGO_CONNECT_FAIL = 251;
    const ERR_MONGO_SAVE_FAIL = 252;
    const ERR_MONGO_FINDONE_FAIL = 253;
    const ERR_MONGO_FIND_FAIL = 254;
    const ERR_MONGO_INSERT_FAIL = 255;
    const ERR_MONGO_DELETE_FAIL = 256;
    const ERR_MONGO_UPDATE_FAIL = 257;
    const ERR_MONGO_COUNT_FAIL = 258;
    const ERR_MONGO_FINDONE_EMPTY = 259;
    const ERR_MONGO_COLLECTIONS_FAIL = 260;
}

?>
