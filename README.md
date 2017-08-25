# utilities-bundle
Usefull symfony code. That applicable to any web-project. Project created to reuse code between different symfony 
projects. Feature added than required.


### Mail cluster
To enable mail cluster add service with ClusterMailer:

    app.mailer:
        class:                      Multifinger\UtilitiesBundle\Service\ClusterMailer
        arguments:                  ['@multifinger.app_settings', %multifinger_mail_nodes%]

optionally add whitelist:

    app.mailer:
    ...
        calls:
            - [setWhitelist, [email@for.test]]
            
or blacklist:

    app.mailer:
    ...
        calls:
            - [setBlacklist, [some@email, another@email]]

use this service as usual Swift_Mailer (currently only send method supported)

