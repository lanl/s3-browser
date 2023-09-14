# s3-browser
https://www.osti.gov/biblio/1866981-s3-browser


Web based application to manage files and buckets via the AWS S3 REST Api. This was manily designed for the ceph rados gateway. It may or may not work with other endpoints. I know there are issues with Google Cloud Storage. 

You can run application as is by cloning this git directory, setting config values, renaming the config file, and running composer install. You must have a webserver capable of processing php files to do this.

You can also build this application as a standalone docker image and running with docker. Change config values, rename config file, `docker build -t username/s3-browser . && docker run -d username/s3-browser -p 4443:443`

---

© 2022. Triad National Security, LLC. All rights reserved.
This program was produced under U.S. Government contract 89233218CNA000001 for Los Alamos
National Laboratory (LANL), which is operated by Triad National Security, LLC for the U.S.
Department of Energy/National Nuclear Security Administration. All rights in the program are
reserved by Triad National Security, LLC, and the U.S. Department of Energy/National Nuclear
Security Administration. The Government is granted for itself and others acting on its behalf a
nonexclusive, paid-up, irrevocable worldwide license in this material to reproduce, prepare
derivative works, distribute copies to the public, perform publicly and display publicly, and to permit
others to do so.
