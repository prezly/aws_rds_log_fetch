#!/usr/bin/env bash

DB_NAME=${1:-production-pgsql}

FILE=${2:-postgresql.log.$(date +%F-%H --date "1 hour ago")}

COUNTER=1
LASTFOUNDTOKEN=0
PREVIOUSTOKEN=0

rm -f ${FILE}

while [  $COUNTER -lt 100 ]; do
	echo "Lets try and get ${FILE}.${COUNTER}"
	echo "The starting-token will be set to ${LASTFOUNDTOKEN}"
	PREVIOUSTOKEN=${LASTFOUNDTOKEN}

	aws rds download-db-log-file-portion --db-instance-identifier ${DB_NAME} --log-file-name error/${FILE} --starting-token ${LASTFOUNDTOKEN}  --debug --output text 2>>${FILE}.${COUNTER}.debug >> ${FILE}.${COUNTER}
	LASTFOUNDTOKEN=`grep "<Marker>" ${FILE}.${COUNTER}.debug | tail -1 | tr -d "<Marker>" | tr -d "/" | tr -d " "`

	echo "LASTFOUNDTOKEN is ${LASTFOUNDTOKEN}"
	echo "PREVIOUSTOKEN is ${PREVIOUSTOKEN}"

	if [ ${PREVIOUSTOKEN} == ${LASTFOUNDTOKEN} ]; then
		echo "No more new markers, exiting"
		rm -f ${FILE}.${COUNTER}.debug
		rm -f ${FILE}.${COUNTER}
		exit;
	else
		echo "Marker is ${LASTFOUNDTOKEN} more to come ... "
		echo " "
		rm -f ${FILE}.${COUNTER}.debug
		PREVIOUSTOKEN=${LASTFOUNDTOKEN}
	fi

	cat ${FILE}.${COUNTER} >> ${FILE}
	rm -f ${FILE}.${COUNTER}

	let COUNTER=COUNTER+1
done

