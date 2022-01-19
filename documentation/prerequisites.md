# Prerequisites based on usecases

This document list the requirements that have to be met in order to perform the desired usecase.

## Connect a local tool to cerebrate
- **Networking**: The *cerebrate* application must be able to contact the local tool service. That means the address and the port of the local must be reachable by *cerebrate*.
- **User permissions**: Depends on the actions performed by Cerebrate on the local tool.
    - Example: For a standard MISP configuration, a simple user with the `user` role is enough for Cerebrate to pass the health check.

## Conect two cerebrate instances together
- **Networking**: The two *cerebrate* applications must be able to contact each others. That means the address and the port of both tools must be reachable by the other one.
- **User permissions**: No specific role or set of permission is required. Any user role can be used.

## Connect two local tools through cerebrate
- **Networking**: The two *cerebrate* applications must be able to contact each others. That means the address and the port of both tools must be reachable by the other one. This also applies to both the local tools.
- **User permissions**: Depends on the actions performed by Cerebrate on the local tool.
    - Example: For a standard MISP configuration, in order to have two instance connected, the API key used by *cebrate* to orchestrate the inter-connection must belong to a user having the `site-admin` permission flag. This is essential as only the `site-admin` permission allows to create synchronisation links between MISP instances.