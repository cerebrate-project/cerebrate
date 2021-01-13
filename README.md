# cerebrate

The Cerebrate Sync Platform core software.  Cerebrate is an open-source platform meant to act as a trusted contact information provider and interconnection orchestrator for other security tools.

It is currently being built under the MeliCERTes v2 project and is heavily work in progress.

# Current features

- Repository of organisations and individuals
- Maintain signing and encryption keys
- Maintain affiliations between organisations and individuals

## Screenshots

List of individuals along with their affiliations

![List of individuals](/documentation/images/individuals.png)

Adding organisations

![Adding an organisation](/documentation/images/add_org.png)

Everything is available via the API, here an example of a search query for all international organisations in the DB.

![API query](/documentation/images/orgs_api.png)

Managing public keys and assigning them to users both for communication and validating signed information shared in the community

![Encryption key management](/documentation/images/add_encryption_key.png)

# Requirements and installation

The platform is built on CakePHP 4 along with Bootstrap 4 and shares parts of the code-base with [MISP](https://www.github.com/MISP).

The installation is documented at the following location [INSTALL/INSTALL.md](INSTALL/INSTALL.md)

Hardware requirements:

A webserver with 4GB of memory and a single CPU core should be plenty for the current scope of Cerebrate. This might increase over the time with additional features being added, but the goal is to keep Cerebrate as lean as possible.

# License

~~~~
    The software is released under the AGPLv3.

    Copyright (C) 2019, 2020  Andras Iklody
    Copyright (C) CIRCL - Computer Incident Response Center Luxembourg
~~~~
