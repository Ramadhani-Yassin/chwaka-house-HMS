<div align="center">
	<a href="https://chwakahouse.co.tz/"><img src="https://forums.qloapps.com/assets/uploads/system/site-logo.png?v=hkl8e1230fo" alt="Chwaka House"></a>
	<br>
	<p>
		<b>The Chwaka House - An open source and free platform to launch your own hotel booking website</b>
	</p>
</div>

<p align="center">
	<a href="#"><img src="https://img.shields.io/badge/Download-Download%20Chwaka%20House%20-brightgreen" alt="Download"></a>
	<a href="#"><img src="https://img.shields.io/badge/Documentation-Guide-yellowgreen" alt="Documentation"></a>
	<a href="#"><img src="https://img.shields.io/badge/Forum-Help%2FSupport-green" alt="Forum"></a>
	<a href="#"><img src="https://img.shields.io/badge/Addons-Plugins-blueviolet" alt="Addons"></a>
	<a href="#"><img src="https://img.shields.io/badge/Contact-Get%20In%20Touch-blue" alt="Contact us"></a>
	<a href="/LICENSE.md"><img src="https://img.shields.io/badge/License-OSL%20V3-green" alt="License"></a>
</p>

## Topics
- [Topics](#topics)
	- [Introduction](#introduction)
	- [Requirements](#requirements)
		- [Hosted Server Configurations](#hosted-server-configurations)
		- [Local Server Configurations](#local-server-configurations)
	- [Installation and Configuration](#installation-and-configuration)
	- [License](#license)
	- [Security Vulnerabilities](#security-vulnerabilities)
	- [Documentation & Demo](#documentation--demo)
	- [Credits](#credits)


### Introduction

The Chwaka House is a hotel management system, a true open-source hotel reservation system and booking engine. The system is dedicated to channeling the power of the open-source community to serve the hospitality industry.

From small independent hotels to big hotel chains, The Chwaka House is a one-stop solution for all your hotel business needs.

You will be able to launch your hotel website, showcase your property and take and manage bookings.

### Requirements

In order to install The Chwaka House you will need the following server configurations for hosted and local servers.
The system compatibility will also be checked by the system with installation and if the server is not compatible then the installation will not move ahead.

#### Hosted Server Configurations

* **Web server**: Apache 1.3, Apache 2.x, Nginx or Microsoft IIS
* **PHP  version**: PHP 8.1+ to PHP 8.4
* **MySQL version**:  5.7+ to 8.4 installed with a database created
* SSH or FTP access (ask your hosting service for your credentials)
* In the PHP configuration ask your provider to set memory_limit to "128M", upload_max_filesize to "16M" ,    max_execution_time to "500" and allow_url_fopen "on"
* SSL certificate if you plan to process payments internally (not using PayPal for instance)
* **Required PHP extensions**: PDO_MySQL, cURL, OpenSSL, SOAP, GD, SimpleXML, DOM, Zip, Phar

#### Local Server Configurations

* **Supported operating system**: Windows, Mac, and Linux
* **A prepared package**: WampServer (for Windows), Xampp (for Windows and Mac) or EasyPHP (for Windows)
* **Web server**: Apache 1.3, Apache 2.x, Nginx or Microsoft IIS
* **PHP**: PHP 8.1+ to PHP 8.4
* **MySQL** 5.7+ to 8.4 installed with a database created
* In the PHP configuration, set memory_limit to "128M", upload_max_filesize to "16M" and max_execution_time to "500"
* **Required PHP extensions**: PDO_MySQL, cURL, OpenSSL, SOAP, GD, SimpleXML, DOM, Zip, Phar

### Installation and Configuration

**1.** You can install The Chwaka House easily after downloading. There are easy steps for the installation process. Please visit the official website or contact support for installation guidance.

**2.** Or you can use a docker image if available. For docker images, please refer to the official documentation or contact support. <br>
* Docker pull command (example)
~~~
docker pull chwakahouse/chwaka-house
~~~

### License

The Chwaka House Core is licensed under OSL-3.0 and modules may have their applicable license, LICENSE.md, kept inside their root directories, while other modules may be licensed under AFL-3.0.

The online copy of OSL-3.0 can be found at [https://opensource.org/licenses/OSL-3.0](https://opensource.org/licenses/OSL-3.0).

The online copy of AFL-3.0 can be found at [https://opensource.org/licenses/AFL-3.0](https://opensource.org/licenses/AFL-3.0).

### Security Vulnerabilities

Please don't disclose security vulnerabilities publicly. If you find any security vulnerability in The Chwaka House then please email us: mailto:support@chwakahouse.co.tz.

### Documentation & Demo

Documentation and demo will be available soon. For now, please visit the [official website](https://chwakahouse.co.tz/) or contact support for more information.

### Credits
Crafted with :heart: at [Webkul](https://webkul.com)  |  Chwaka House Official Website: https://chwakahouse.co.tz/
