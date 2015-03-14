# PROJECT OVERVIEW #

The objective of the Open Source Google Apps Provisioning Toolkit is to provide a browser-based interface for creating and updating user accounts in Google Apps.

The toolkit can be configured to address two common use cases:

1. bulk provisioning or updating user accounts from information stored in a CSV, LDAP, or SQL data source

2. providing a web page where users can self-register with Google Apps, after optionally first authenticating against an existing user data store (e.g. SAML-based SSO, LDAP, or SQL)

View a brief presentation: http://docs.google.com/Presentation?id=dcpd95bh_52vshqtjq3


# SYSTEM REQUIREMENTS #

There are two ways to run the toolkit:  1) as a single download on the [free VMware Player](http://www.vmware.com/products/player/), or 2) using Apache/PHP.

**VMware Requirements:**

- Windows, Linux, or any OS that supports the [free VMware Player](http://www.vmware.com/products/player/).  View a quick [presentation](http://docs.google.com/Presentation?id=dcm3w56_2683ntdzgq) on how to run the toolkit with the VMware image

**Apache/PHP Requirements:**

- Any O.S. which supports PHP and Apache 2.0

- Apache 2.0 web server (Browse to http://httpd.apache.org for information regarding the Apache web server)

- PHP 5.x (http://www.php.net) compiled with the following extensions:
> - OpenLDAP (http://www.openldap.org), needed only for LDAP support.

> - OpenSSL (http://www.openssl.org), needed only if communicating via SSL

> - mySQL (http://www.mysql.com), needed only for mySQL support

> - FreeTDS (http://www.freetds.org), needed only for MS SQL support

> - XML (XML, simpleXML, tinyXML, etc.  Installed by default by PHP installation)

> - GD library (http://www.libgd.org/), needed for CAPTCHA image creation

- Python 2.2 or later (http://www.python.org)

- GData Python client library 1.0.8 (installation files can be found in the libraries directory, http://code.google.com/p/gdata-python-client)

- ElementTree (installation files can be found in the libraries directory, http://effbot.org/zone/element-index.htm)

For more information on the GData Python client library, please see the project on code.google.com's hosting service here: http://code.google.com/p/gdata-python-client/

Please note that many of the system requirement components may be installed by default by your Linux distribution.  Please visit your Linux distribution's download site for more information.


# INSTALLATION using Apache/PHP #

Copy/upload files to a working directory within your web server's document root or your public HTML directory, e.g. /var/www/html/GoogleAppsToolKit/.

Edit the configuration file found in  'admin/config.php' and enter the required information:


**Google Apps Domain Settings**

> domain - the Google Apps domain name, e.g. MyGoogleAppsDomain.com

> admin - an administrator account for the Google Apps domain, e.g. administrator

> password - the administrator account password

> allow\_account\_deletion - valid entries: 'yes' or 'no'

> A selection of 'no' suspends the user account instead of deleting the account

> Please note that a selection of 'yes' will delete all user account information (i.e. email, contacts, etc.) Once a user account is deleted it cannot be restored.


**Server Variables**

> dir\_upload - the directory where csv files and logs will be saved. This directory needs to be writeable.

> log\_file - log file to record accounts created during the self provisioning process

> log\_errors - errors log file to record errors that occur during the self provisioning process


**Database Settings**

> DB\_TYPE - Define the type of database you'll be employing in the batch process.

> Valid entries:

> csv: CSV

> mysql: MySQL

> mssql: Microsoft SQL

> ldap: LDAP

> sso: Single Sign-On   **only available in self provisioning section**



**SSO Settings**

> This application employs a SAML-based Single Sign-On function that enables you to use any SAML 2.0 compliant SSO solution.   Pleaser refer to Google's SAML Single Sign-ON (SSO) Service for Google Apps, http://code.google.com/apis/apps/sso/saml_reference_implementation.html, for more information.


> THIS\_SERVER - The server hosting the application.

> SSO\_URL - Url processing the SAML request.  Where users get authenticated and SAML response is generated.

> SERVICE\_URL - Url processing the SAML response.  By default it is toolkit\_dir/selfprovisioning/SAML.php.

> PUB\_KEY - Location of public certificate.  This certificate is used to validate SAML responses.



**MySQL/MSSQL Settings**

> DB\_SERVER - Server name or IP of database host.

> DB\_PORT - Port to use to connect to server.

> DB\_UNAME - Server username.

> DB\_PWORD - Server password.

> DB\_NAME - Database/catalog name.

> DB\_TABLE - Table name storing users' information.

> DB\_COL\_USERNAME - Table field name to be mapped as the Google Apps account username.

> DB\_COL\_FIRSTNAME - Table field name to be mapped as the Google Apps account given name.

> DB\_COL\_LASTNAME - Table field name to be mapped as the Google Apps account family name.

> DB\_COL\_PASSWORD - Table field name to be mapped as the Google Apps account password.



**LDAP Settings**

> LDAP\_SERVER - Server name or IP of LDAP host.

> LDAP\_PORT - Port to use to connect to server.

> LDAP\_PROTOCOL - LDAP protocol

> LDAP\_REFERRALS - LDAP referrals

> LDAP\_BIND\_RDN - LDAP login name, bind DN

> LDAP\_BIND\_PASSWORD - LDAP login password

> LDAP\_BASE\_DN - Base DN for user's group

> LDAP\_FILTER - Filter to be applied to LDAP to isolate desired users.

> LDAP\_USERNAME - LDAP field name to be mapped as the Google Apps account username.

> LDAP\_FIRSTNAME - LDAP field name to be mapped as the Google Apps account given name.

> LDAP\_LASTNAME - LDAP field name to be mapped as the Google Apps account family name.

> LDAP\_PASSWORD - Set to 'default' or 'field'

> For security purposes LDAP encrypts passwords and are not readable. Therefore, this application gives you the option to either use a string (LDAP\_DEFAULT\_PASSWORD) as the default passwords for all users or to use a readable LDAP field or combinations of LDAP fields (LDAP field 1 + LDAP field 2 ...) as the users' password.  Alternative, you can generate $field by writing your own custom function. LDAP\_DEFAULT\_PASSWORD - Default password.  Employed if LDAP\_PASSWORD is set to 'default'.

# USAGE #

Finally, browse to the admin directory, e.g. http://mywebserver.com/GoogleAppsToolKit/admin/, to execute the batch functions or to the selfprovisioning directory, e.g. http://mywebserver.com/GoogleAppsToolKit/selfprovisioning/, to execute the self provisioning functions.

To discuss the toolkit, please visit the project's Google Group: http://groups.google.com/group/google-apps-provisioning-toolkit

Project originally developed by SADA Systems (http://sadasystems.com)