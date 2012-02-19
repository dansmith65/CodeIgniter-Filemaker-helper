WHAT
This folder contains a sample database and model/view/controller code for CodeIgniter to access the database via CodeIgniter-Filemaker-helper.
The database used is DevCon2Go11.fp7 available from: http://www.filemaker.com/developers/devcon/2011/getdevcon2go.html

REQUIREMENTS
- FileMaker Server (was designed for/built with v11)
	- with custom web publishing enabled
- Web server
- CodeIgniter (was designed for/built with v2.0)

Steps to use this code:
1. Copy/uncompress the following files in your FileMaker Server Database folder, and open them in FileMaker Server
	CodeIgniter-Filemaker-helper/sample/database/*
2. Install a fresh copy of CodeIgniter on your web server.
3. Copy the following files to the new CodeIgniter application folder created in step 2
	CodeIgniter-Filemaker-helper/application/*
	CodeIgniter-Filemaker-helper/sample/application/*
4. Modify this config file if your FileMaker server is not on localhost:
	CodeIgniter-Filemaker-helper/application/config/fmdb.php
5. Go to: http://{hostname}/{path/to/CodeIgniter}/index.php/speaker

database login:
user: admin
password: devcon
