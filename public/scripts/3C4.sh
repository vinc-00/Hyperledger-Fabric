#!/bin/bash

cd $HOME
HFH=Hyperledger_Fabric_Network/Fabric

cd $HFH/fabric-ca-client
export FABRIC_CA_CLIENT_HOME=$PWD
cp ../fabric-ca-server-tls/ca-cert.pem tls-root-cert/tls-ca-cert.pem

#enroll the TLS CA admin
./fabric-ca-client enroll -d -u https://!TLS_ADMIN_USERNAME:!TLS_ADMIN_PASSWORD@localhost:!TLS_CA_SERVER_PORT --tls.certfiles tls-root-cert/tls-ca-cert.pem --enrollment.profile tls --mspdir tls-ca/!TLS_ADMIN_USERNAME/msp

#register the CA admin in order to obtain its TLS certificate
./fabric-ca-client register -d --id.name !CA_ADMIN_USERNAME --id.secret !CA_ADMIN_PASSWORD -u https://localhost:!TLS_CA_SERVER_PORT --tls.certfiles tls-root-cert/tls-ca-cert.pem --mspdir tls-ca/!TLS_ADMIN_USERNAME/msp

#enroll the CA admin
./fabric-ca-client enroll -d -u https://!CA_ADMIN_USERNAME:!CA_ADMIN_PASSWORD@localhost:!TLS_CA_SERVER_PORT --tls.certfiles tls-root-cert/tls-ca-cert.pem --enrollment.profile tls --csr.hosts "!CA_CSR_HOSTS" --mspdir tls-ca/!CA_ADMIN_USERNAME/msp

#create the folders for the enrollment ca
cd ..
mkdir fabric-ca-server-!CA_NAME
cd fabric-ca-server-!CA_NAME
mkdir tls

#retrieve TLS certificate of the CA admin
cp ../fabric-ca-client/tls-ca/!CA_ADMIN_USERNAME/msp/signcerts/cert.pem tls/
cp ../fabric-ca-client/tls-ca/!CA_ADMIN_USERNAME/msp/keystore/* tls/key.pem

#copy the binary of the CA server
cp ../../fabric-samples/bin/fabric-ca-server .

#initilize the CA server
./fabric-ca-server init -b !CA_ADMIN_USERNAME:!CA_ADMIN_PASSWORD

#change the configuration file of the CA server
sed -i 's/port: 7054/port: !CA_SERVER_PORT/' fabric-ca-server-config.yaml #changes listening port of the ca server
sed -z -i 's/# Enable TLS (default: false)\n  enabled: false/# Enable TLS (default: false)\n  enabled: true/' fabric-ca-server-config.yaml #activates the TLS protocol
sed -z -i 's/listening port\n  certfile:\n  keyfile:/listening port\n  certfile: tls\/cert.pem\n  keyfile: tls\/key.pem/' fabric-ca-server-config.yaml #specifies the tls certificate
sed -z -i 's/# Name of this CA\n  name:/# Name of this CA\n  name: !CA_NAME/' fabric-ca-server-config.yaml #indicates the name of this ca
sed -z -i 's/pathlength: 1/pathlength: !PATH_LENGTH/' fabric-ca-server-config.yaml #heigth of CA tree
sed -z -i 's/listenAddress: 127.0.0.1:9443/listenAddress: 127.0.0.1:9444/' fabric-ca-server-config.yaml #change the operation port of this CA

#remove the old certificate
rm -rf msp 
rm ca-cert.pem

cd $HOME


