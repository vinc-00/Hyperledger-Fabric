#!/bin/bash

cd $HOME
HFH=Hyperledger_Fabric_Network/Fabric

cd $HFH/fabric-ca-client

export FABRIC_CA_CLIENT_HOME=$PWD

./fabric-ca-client enroll -d -u https://!CA_ADMIN_USERNAME:!CA_ADMIN_PASSWORD@!HOSTNAME:!CA_SERVER_PORT --tls.certfiles tls-root-cert/tls-ca-cert.pem --mspdir !CA_NAME/!CA_ADMIN_USERNAME/msp

#register and enroll the intermediate CA admin to obtain TLS certificate
./fabric-ca-client register -d --id.name !INT_CA_ADMIN_USERNAME --id.secret !INT_CA_ADMIN_PASSWORD -u https://localhost:!TLS_CA_SERVER_PORT --tls.certfiles tls-root-cert/tls-ca-cert.pem --mspdir tls-ca/!TLS_ADMIN_USERNAME/msp
./fabric-ca-client enroll -d -u https://!INT_CA_ADMIN_USERNAME:!INT_CA_ADMIN_PASSWORD@localhost:!TLS_CA_SERVER_PORT --tls.certfiles tls-root-cert/tls-ca-cert.pem --enrollment.profile tls --csr.hosts "!INT_CA_CSR_HOSTS" --mspdir tls-ca/!INT_CA_ADMIN_USERNAME/msp

#register the intermediate CA in order to obtain its identity
./fabric-ca-client register -u https://!HOSTNAME:!CA_SERVER_PORT --id.name !INT_CA_ADMIN_USERNAME --id.secret !INT_CA_ADMIN_PASSWORD --id.attrs '"hf.Registrar.Roles=user,admin","hf.Revoker=true","hf.IntermediateCA=true"' --tls.certfiles tls-root-cert/tls-ca-cert.pem --mspdir !CA_NAME/!CA_ADMIN_USERNAME/msp

#create the intermediate CA server
cd ..
mkdir fabric-ca-server-!INT_CA_NAME
cd fabric-ca-server-!INT_CA_NAME
cp ../../fabric-samples/bin/fabric-ca-server .
mkdir tls 
cp ../fabric-ca-client/tls-ca/!INT_CA_ADMIN_USERNAME/msp/signcerts/cert.pem ./tls
cp ../fabric-ca-client/tls-ca/!INT_CA_ADMIN_USERNAME/msp/keystore/* ./tls/key.pem
cp ../fabric-ca-client/tls-root-cert/tls-ca-cert.pem ./tls
./fabric-ca-server init -b !INT_CA_ADMIN_USERNAME:!INT_CA_ADMIN_PASSWORD

#edit the configuration file
sed -i 's/port: 7054/port: !INT_CA_SERVER_PORT/' fabric-ca-server-config.yaml #changes listening port of the int-ca server
sed -z -i 's/# Enable TLS (default: false)\n  enabled: false/# Enable TLS (default: false)\n  enabled: true/' fabric-ca-server-config.yaml #activates the TLS protocol
sed -z -i 's/listening port\n  certfile:\n  keyfile:/listening port\n  certfile: tls\/cert.pem\n  keyfile: tls\/key.pem/' fabric-ca-server-config.yaml #specifies the tls certificate
sed -z -i 's/cn: fabric-ca-server/cn:/' fabric-ca-server-config.yaml #deletes the common name (useless for the intermediate CAs)
sed -z -i 's/pathlength: 1/pathlength: !PATH_LENGTH/' fabric-ca-server-config.yaml #heigth of CA tree
sed -z -i 's/parentserver:\n    url:\n    caname:/parentserver:\n    url: https:\/\/!CA_ADMIN_USERNAME:!CA_ADMIN_PASSWORD@!HOSTNAME:!CA_SERVER_PORT\n    caname: !CA_NAME/' fabric-ca-server-config.yaml #edit the intermediate section
sed -z -i 's/enrollment:\n    hosts:/enrollment:\n    hosts: !HOSTNAME/' fabric-ca-server-config.yaml 
sed -z -i 's/profile:/profile: ca/' fabric-ca-server-config.yaml 
sed -z -i 's/\n\n  tls:\n    certfiles:/\n\n  tls:\n    certfiles: tls\/tls-ca-cert.pem/' fabric-ca-server-config.yaml 
sed -z -i 's/# Name of this CA\n  name:/# Name of this CA\n  name: !INT_CA_NAME/' fabric-ca-server-config.yaml #indicates the name of this ca
sed -z -i 's/listenAddress: 127.0.0.1:9443/listenAddress: 127.0.0.1:9445/' fabric-ca-server-config.yaml #change the operation port of this CA

rm ca-cert.pem
rm -rf msp

cd $HOME