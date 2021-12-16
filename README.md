# s3-browser-pub

Web based application to manage files and buckets via the AWS S3 REST Api. 

You can run application as is by cloning this git directory, setting config values, renaming the config file, and running `composer install`. You must have a webserver capable of processing php files to do this. 

You can also build this application as a standalone docker image and running with docker. Change config values, rename config file, `docker build -t username/s3-browser . && docker run -d username/s3-browser -p 4443:443`


Dependencies

```
AWS SDK for PHP / Amazon Web Services / Apache License 2.0
Bootstrap / The Bootstrap Authors / MIT
Bootstrap Icons / The Bootstrap Authors / MIT
Dropzone.js / Matias Meno / MIT
CodeMirror / Marijn Haverbeke / MIT
List.js / Jonny Strömberg / MIT
jsonlint / Zachary Carter / MIT
```
