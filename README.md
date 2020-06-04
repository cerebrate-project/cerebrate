# cerebrate
The Cerebrate Sync Platform core software

Cerebrate is an open-source platform meant to act as a trusted contact information provider and interconnection orchestrator for other security tools.

It is currently being built under the Melicertes v2 project and is heavily work in progress.

Currently it maintains a repository of organisations and individuals along with signing keys and their affiliations.

The platform is built on CakePHP 4 along with Bootstrap 4 and shares parts of the code-base with MISP.

#### Screnshots

List of individuals along with their affiliations

![List of individuals](/documentation/images/individuals.png)

Adding organisations

![Adding an organisation](/documentation/images/orgs_api.png)

Everything is available via the API, here an example of a search query for all international organisations in the DB.

![API query](/documentation/images/orgs_api.png)

Managing public keys and assigning them to users both for communication and validating signed information shared in the community

![Encryption key management](add_encryption_key.png)
