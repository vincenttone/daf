#!/bin/sh
proj_name=daf
output_dir=output

output_file_name=${proj_name}.tar.gz
output_tmp_dir=${output_dir}/${proj_name}
package_dir=${output_dir}/${output_file_name}
[ -d $output_dir ] || mkdir $output_dir
[ -f $package_dir ] && rm -rf $package_dir
if [ -d $output_tmp_dir ]; then 
    rm -rf $output_tmp_dir
fi
mkdir -p $output_tmp_dir
app_dir=app
bin_dir=bin
conf_dir=conf
data_dir=data
log_dir=log
script_dir=script
static_dir=public
cp -r $app_dir $bin_dir $conf_dir $script_dir $static_dir $output_tmp_dir
echo Build at `date "+%F %X"` > ${output_tmp_dir}/BUILD.INFO
echo Rm all svn dir.
find $output_tmp_dir -name .svn |xargs rm -rf
echo Package the ${package_dir} from ${output_tmp_dir}.
echo Create data and log dirs.
[ -d ${output_tmp_dir}/${data_dir} ] || mkdir ${output_tmp_dir}/${data_dir}
[ -d ${output_tmp_dir}/${log_dir} ] || mkdir ${output_tmp_dir}/${log_dir}
chmod -R +x ${output_tmp_dir}/${bin_dir}
chmod -R +x ${output_tmp_dir}/${script_dir}
cd $output_dir
tar cvzf $output_file_name $proj_name
echo Clean up.
[ -d $proj_name ] && rm -rf $proj_name
echo Build finish!
