# MagentoPackager

[![Latest Stable Version](https://poser.pugx.org/ameenross/magento_packager/v/stable)](https://packagist.org/packages/ameenross/magento_packager)
[![Total Downloads](https://poser.pugx.org/ameenross/magento_packager/downloads)](https://packagist.org/packages/ameenross/magento_packager)
[![Latest Unstable Version](https://poser.pugx.org/ameenross/magento_packager/v/unstable)](https://packagist.org/packages/ameenross/magento_packager)
[![License](https://poser.pugx.org/ameenross/magento_packager/license)](https://packagist.org/packages/ameenross/magento_packager)

A tool to package Magento 1 extensions for Magento Connect.

# Installation

```sh
composer require ameenross/magento_packager
```

# Usage

1. Create a file called `magepkg.xml` in your extension directory, make sure the
metadata corresponds to your package:
```xml
<?xml version="1.0"?>
<package>
    <name>My Extension</name>
    <version/>
    <stability>stable</stability>
    <license uri="https://opensource.org/licenses/GPL-3.0">GPL-3.0</license>
    <channel>community</channel>
    <extends/>
    <summary>This is a short description of My Extension.</summary>
    <description>
        This is a longer description of the functionality of My Extension.
        It does many things.
    </description>
    <notes/>
    <authors>
        <author>
            <name>Me</name>
            <user>me</user>
            <email>me@example.com</email>
        </author>
    </authors>
    <date/>
    <time/>
    <contents/>
    <compatible/>
    <dependencies>
        <required>
            <php>
                <min>5.4.0</min>
                <max>6.0.0</max>
            </php>
        </required>
    </dependencies>
</package>
```
2. Invoke the CLI script. It needs to be fed a TAR archive containing your
extension. It also needs to be told where to store the resulting package (if not
in the current working directory), its version and any release notes. Example:
```sh
git archive HEAD | vendor/bin/magepkg -o releases/ --version="1.0.0" --notes="First stable release."
```
