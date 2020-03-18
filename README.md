# Form Database Plugin

The **Form Database** Plugin is for [Grav CMS](http://github.com/getgrav/grav).  
- Dependecies : 
   - [Form Plugin v2.0](https://github.com/getgrav/grav-plugin-form) 
   - [Database v1.0.0](https://github.com/getgrav/grav-plugin-database) 
- saves forms results to your **MySQL, Postgre or SQLite** Databases (based on PDO).
*Other [PDO compatible DB](https://www.php.net/manual/fr/pdo.drivers.php) should work from scratch or be fairly easy to add*

## Configuration

### General Settings
Fill in the requires information in the `user/plugins/from-database/from-database.yaml` file, or use the Admin Template to make it in a GUI.
So far only 
```
enabled: true

server: localhost 
# or mysql_server: localhost (v1.0.1)

port: 3306 
# or mysql_port: 3306 (v1.0.1)

username: 
# or mysql_username: (v1.0.1)

password: 
# or mysql_password: (v1.0.1)

#################### additional params since 2.0 ##################################
engine: mysql 
# Available engines are mysql, pgsql and sqlite. default is mysql for backwards compatibility.

db: <<Your DB Name>> 
# set the database globally and override it in the forms if needed

table: <<Table Name>> 
# set the table globally and override it in the forms if needed 

array_separator: | 
# Separator character to store array based form results (like checkboxes) as concatenated string with seperator. default is ";".
```

### Individual Form Settings
Additional to the general Settings you need following Form specific settings:
```
# can be set globally 
db: <<Your DB Name>>

# can be set globally
table: <<Table Name>>

# for each of your table fields, set the value from the form fields
# You can use twig to do some computation on the form field value
# you can also set a constant
table_fields: 
    <<DB_Field1>>: <<Form_Field1|twig_string|hard_coded_data>>
    <<DB_Field2>>: <<Form_Field1|twig_string|hard_coded_data>>
    
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
                table: surveys
                table_fields:
                        # using form's filed named 'e-mail'
                    e-mail: email
                        # using form's filed named 'phone-fixnet'
                    phone-fixnet: phone 
                        # using twig
                    stored: "{{now|date('HisDdMy') }}"
                        # using hardcoded constant
                    form_id: "form_01" # using hardcoded constant
                    
```
## Changes
this repo is the new master for this plugin
original repo from Andy Scherer (scan5415) 
https://github.com/scan5415/grav-plugin-form-database
