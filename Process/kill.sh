ps -ef | grep master:Process | grep -v g | cut -c 9-15 | xargs kill -9
ps -ef | grep worker:Process | grep -v g | cut -c 9-15 | xargs kill -9
rm -rf "master.pid"
rm -rf "child.pid"