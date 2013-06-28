#!/usr/bin/env bash

openssl genrsa -out ssl.key 1024
openssl req -new -key ssl.key -out ssl.csr
openssl x509 -req -in ssl.csr -signkey ssl.key -out ssl.crt
