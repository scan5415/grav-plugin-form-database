# Form Database Plugin

The **Form Database** Plugin is for [Grav CMS](http://github.com/getgrav/grav).  This is a extension to the normal **Form-Plugin**.
With this plugin you can save added forms to your **MySQL Database**.

## Configuration

### General Settings
Fill in the requires information in the `user/plugins/from-database/from-database.yaml` file, or use the Admin Template to make it in a GUI.

```
enabled: true
mysql_server: localhost
mysql_port: 3306
mysql_username:
mysql_password:
```

### Individual Form Settings
Additional to the general Settings you need following Form specific settings:
```
db: <<Your DB Name>>
table: <<Table Name>>
fields: { <<DB_Field1>>: <<Form_Field1>>, <<DB_Field2>>: <<Form_Field2>>, ... }
```

**Example**
```
...
    process:
        -
            email:
                from: '{{ config.plugins.email.from }}'
                to: ['{{ config.plugins.email.to }}', '{{ form.value.email }}']
                subject: '[Site Contact Form] {{ form.value.name|e }}'
                body: '{% include ''forms/data.txt.twig'' %}'
        -
            database:
                db: myCompany
                table: contacts
                fields: { name: name, e-mail: email, phone-fixnet: phone }
```
