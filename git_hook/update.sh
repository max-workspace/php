#!/bin/sh

echo "exec git hook";
echo $(pwd);
echo $(whoami);
echo $0;
echo $1;
echo $2;
echo $3;
commit_id=$3;
commit_author=$(git log --pretty=format:%an ${commit_id} -1);
commit_desc=$(git log --pretty=format:%s ${commit_id} -1);
commit_time=$(git log --pretty=format:%ad ${commit_id} -1);
message='meteo_app '${commit_time}' '${commit_author}' '${commit_desc}
echo $message;
php /usr/git/dingTalkRobot.php test_robot "${message}"
echo $?;
