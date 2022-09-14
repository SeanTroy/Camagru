### How to use

Online version, where you can sign up and test all the functionality, available [here](https://camagru.pekkalehtikangas.fi).

If you want to clone the git to your own computer:

- you need to install some development environment, like [Bitnami MAMP](https://bitnami.com/stack/mamp)
- after installing, clone the git inside the 'apache2/htdocs' folder in your install path:
```
git clone https://github.com/SeanTroy/Camagru.git Camagru
```
- modify the $DB_USER and $DB_PASSWORD variables in the file 'config/database.php', with the username and password you provided when installing MAMP
- start the MariaDB and Apache Web servers using 'manager-osx.app' in the MAMP root folder (Manage Servers -> Start All)
- then you can open the app in your browser, using address http://localhost:8080/Camagru
