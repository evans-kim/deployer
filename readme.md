# Github Action with Deployer

### Sources and Requirement
- [Deployer - https://deployer.org](https://deployer.org)

### Prepare 


    ssh-keygen # make ssh keypair
    cd .ssh
    cat id_rsa.pub >> authorized_keys # for private key use
    
    ssh-keyscan -H github.com >> known_hosts
    ssh-keyscan -H yourdomain.com >> known_hosts

    cat known_host # for github secret KNOWN_HOST
    cat id_rsa # for github secret PRIVATE_KEY
    cat id_rsa.pub # for github deploy key

