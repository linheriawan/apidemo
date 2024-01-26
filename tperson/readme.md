#SQL
CREATE TABLE `person` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(60) NULL,
  `gender` VARCHAR(6) NULL,
  `address` VARCHAR(250) NULL,
  `age` INT NULL,
  `hobby` VARCHAR(50) NULL,
  `height` INT NULL,
  CONSTRAINT `PRIMARY` PRIMARY KEY (`id`)
);

# Make Image
minikube start --insecure-registry "192.168.65.0/24"
minikube addons enable registry
kubectl port-forward --namespace kube-system service/registry 5000:80

docker build -t $(minikube ip):5000/person-tes .
Docker Engine => "insecure-registries":["172.16.10.2:5000"]
docker push $(minikube ip):5000/person-tes

<!-- eval $(minikube docker-env)
docker build -t person-tes . 
docker tag person-tes $(minikube ip):5000/person-tes 
docker push $(minikube ip):5000/person-tes
kubectl port-forward --namespace kube-system service/registry 5000:80
minikube image load $(minikube ip):5000/person-tes -->

# run
kubectl apply -f confmap.yaml
kubectl apply -f deployment.yaml

#kubectl apply -f service.yaml
kubectl expose deployment person-tes-dep --type=LoadBalancer --port=80 --target-port=80
#kubectl apply -f ingres.yaml

minikube service person-tes-dep
