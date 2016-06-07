# aws_rds_log_fetch

Script used to fetch hourly logs from RDS instance.
It writes the logs to an error/ folder and has the ability to append to a single file for rsyslog/logstash digestion

I cron the script to be run hourly with the --lasthour pararameter. So at the end of every hour it gets the log file from last hour and writes + appends that.


## How to use


```bash
php getlog.php --dbidentifier=[DBIDENTIFIER] --region=[REGION] --key=[AUTH_KEY] --secret=[AUTO_SECRET]
```

## Other arguments

* --clearfiles: removes existing files in error and overwrites
* --lasthour: only write the log for the last hour and leave others untouched
* --append_to_file=error/allqueries.log: append all logs to a single file

## RDS settings

To use hourly logging change your RDS parameter group parameters to 

log_filename: postgresql.log.%Y-%m-%d-%H


