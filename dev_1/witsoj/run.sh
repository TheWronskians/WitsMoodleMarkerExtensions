#!/bin/bash

find . -type f | grep -v .git | grep -v swp | grep -v run.sh | while read line;
do
	THING=FEEDBACK_CO
	N=`cat $line | grep $THING |  wc -l`
	if [[ "$N" > "0" ]]
	then
		echo $line : $N
		cat $line | grep $THING
	fi
	#sed -i "s:feedback_comments:feedback_witsoj:g" $line
	#sed -i "s:ASSIGNSUBMISSION_FILE:ASSIGNSUBMISSION_WITSOJ:g" $line

done
