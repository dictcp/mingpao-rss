#!/bin/bash
ttime="0700";
for ((;;)); do
  echo waiting; 
  while [ 0 == 0 ] ; do 
    #if [ "`date +%H%M`" == "$ttime" ];then 
    if [ "`date +%H`" == "05" ] || [ "`date +%H`" == "06" ] || [ "`date +%H`" == "07" ]; then 
    #if [ 0 == 0 ]; then
      echo "checking any update @ `date`"
      if [ "`wget -q -O - http://news.mingpao.com/ | grep -Po '<base href="http://news.mingpao.com/[0-9]*/">' | grep -Po '[0-9]*'`" == "`date +%Y%m%d`" ]; then
        echo breaking; 
        break; 
      fi;
    fi; 
    #sleep 30; 
    sleep 300; 
  done; 
  echo "updating at `date`"; 
  for ch in ga gb ca ta; do 
    php gen_rss.php $ch > $ch.rss ;
  done; 
  #sleep 60; 
  sleep 10800; 
done
