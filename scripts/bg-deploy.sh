#!/bin/sh

set -e

# bg-deploy.sh <version> <namespace> <release-name> <docker-image>
NEW_VERSION=$1
NAMESPACE=$2
RELEASE_NAME=$3
DOCKER_IMAGE=$4

CHART_NAME=api
APP_NAME=app

SERVICE_NAME=$RELEASE_NAME-$CHART_NAME-$APP_NAME
DEPLOYMENT_PREFIX=$RELEASE_NAME-$CHART_NAME
DEPLOYMENT_NAME=$DEPLOYMENT_PREFIX-$APP_NAME

CURRENT_VERSION=$(kubectl get svc $SERVICE_NAME -n $NAMESPACE -o jsonpath="{.spec.selector['app\.kubernetes\.io/version']}")
CURRENT_DEPLOYMENT_DOCKER_IMAGE=$(kubectl get pod -n $NAMESPACE -o jsonpath="{.items[*].spec.containers[?(@.name==\"$APP_NAME\")].image}" | cut -d " " -f 1)

if [ "$CURRENT_VERSION" = "$NEW_VERSION" ]; then
  	echo "[INFO] NEW_VERSION is same as the CURRENT_VERSION. Both are at $CURRENT_VERSION"
	exit 0
fi

CURRENT_DEPLOYMENT_NAME=$(kubectl get deploy -l app.kubernetes.io/instance=$RELEASE_NAME,app.kubernetes.io/name=$CHART_NAME-$APP_NAME,app.kubernetes.io/version=$CURRENT_VERSION,app.kubernetes.io/component=backend -n $NAMESPACE -o name | cut -d "/" -f 2)

# If no version specified, grab from container's image tag
if [ -z "$CURRENT_VERSION" ]; then
	CURRENT_DEPLOYMENT_NAME=$(kubectl get deploy -l app.kubernetes.io/instance=$RELEASE_NAME,app.kubernetes.io/name=$CHART_NAME-$APP_NAME,app.kubernetes.io/component=backend -n $NAMESPACE -o name | cut -d "/" -f 2)
	CURRENT_VERSION=$(echo $CURRENT_DEPLOYMENT_DOCKER_IMAGE | cut -d ":" -f 2)
	# Make sure deployment and service have a version
	echo "[PATCH] Patching current version into deployment and service..."
	kubectl patch deploy $CURRENT_DEPLOYMENT_NAME -n $NAMESPACE -p "{\"metadata\": {\"labels\": {\"app.kubernetes.io/version\": \"$CURRENT_VERSION\"}},\"spec\": {\"template\": {\"metadata\": {\"labels\": {\"app.kubernetes.io/version\": \"$CURRENT_VERSION\"}}}}}"
	kubectl patch svc $SERVICE_NAME -n $NAMESPACE -p "{\"spec\": {\"selector\": {\"app.kubernetes.io/version\": \"$CURRENT_VERSION\"}}}"
fi

# Create a migration deployment
echo "[DEPLOY] Creating a migration deployment..."
MIGRATION_DEPLOYMENT_NAME=$DEPLOYMENT_NAME-migration-$NEW_VERSION
kubectl get deploy $CURRENT_DEPLOYMENT_NAME -n $NAMESPACE -o yaml | sed -e "s/$CURRENT_DEPLOYMENT_NAME/$MIGRATION_DEPLOYMENT_NAME/g" -e "s/$CURRENT_VERSION/$NEW_VERSION/g" | kubectl apply -n $NAMESPACE -f -
kubectl scale deploy $MIGRATION_DEPLOYMENT_NAME -n $NAMESPACE --replicas=0
kubectl set image deploy $MIGRATION_DEPLOYMENT_NAME $APP_NAME=$DOCKER_IMAGE -n $NAMESPACE
kubectl patch deploy $MIGRATION_DEPLOYMENT_NAME -n $NAMESPACE -p "{\"spec\": {\"template\": {\"spec\": {\"containers\": [{\"name\": \"$APP_NAME\",\"command\": [\"sh\", \"-c\"],\"args\": [\"composer install --prefer-dist --no-progress --no-suggest --no-interaction && bin/console doctrine:migrations:migrate --no-interaction && echo done\"]}]}}}}"
kubectl scale deploy $MIGRATION_DEPLOYMENT_NAME -n $NAMESPACE --replicas=1

# Wait until migration is completed
echo "[INFO] Waiting for migration to be fully deployed..."
# kubectl rollout status deploy $MIGRATION_DEPLOYMENT_NAME -n $NAMESPACE
# echo "[INFO] Migration has been deployed successfully, running migration scripts..."
attempts=0
until [ "$(kubectl logs $MIGRATION_POD --tail=1 -n $NAMESPACE -c $APP_NAME)" = "done" ]; do
	if [[ "$attempts" -ge 120 ]]; then
		echo "[ERROR] Too many attempts, exiting..."
		kubectl delete deploy $MIGRATION_DEPLOYMENT_NAME -n $NAMESPACE
		exit 1
	fi
	MIGRATION_POD=$(kubectl get pod -n $NAMESPACE -o jsonpath="{.items[*].metadata.name}" | tr " " "\n" | grep $MIGRATION_DEPLOYMENT_NAME | tr "\n" " " | cut -d " " -f 1)
	attempts=$((attempts+1))
	sleep 1;
done;
# Output the results
kubectl logs $MIGRATION_POD -n $NAMESPACE -c $APP_NAME
# Delete migration deployment
echo "[CLEANUP] Migration ran successfully, cleaning up migration deployment..."
kubectl delete deploy $MIGRATION_DEPLOYMENT_NAME -n $NAMESPACE

# Create a new deployment
echo "[DEPLOY] Creating the next version of deployment..."
NEW_DEPLOYMENT_NAME=$DEPLOYMENT_NAME-$NEW_VERSION
kubectl get deploy $CURRENT_DEPLOYMENT_NAME -o yaml -n $NAMESPACE | sed -e "s/$CURRENT_DEPLOYMENT_NAME/$NEW_DEPLOYMENT_NAME/g" -e "s/$CURRENT_VERSION/$NEW_VERSION/g" | kubectl apply -n $NAMESPACE -f -
kubectl set image deploy $NEW_DEPLOYMENT_NAME $APP_NAME=$DOCKER_IMAGE -n $NAMESPACE

echo "[INFO] Waiting for next version to be fully deployed..."
kubectl rollout status deploy $NEW_DEPLOYMENT_NAME -n $NAMESPACE

# Healthcheck
echo "[HEALTHCHECK] WIP..."

# Re-deploy workers
echo "[DEPLOY] Re-deploying workers..."
for WORKER_NAME in "application-requests-worker" "campaigns-worker" "cron-worker" "messages-worker" "reports-worker" "webservices-worker"; do
	kubectl patch deploy $DEPLOYMENT_PREFIX-$WORKER_NAME -n $NAMESPACE -p "{\"metadata\": { \"labels\": { \"app.kubernetes.io/version\": \"$NEW_VERSION\" } },\"spec\": {\"containers\": [{ \"name\": \"$WORKER_NAME\", \"image\": \"$DOCKER_IMAGE\" }],\"template\": {\"metadata\": { \"labels\": { \"app.kubernetes.io/version\": \"$NEW_VERSION\" } }}}}"
done;

echo "[SWITCH] Routing traffic to new version..."
kubectl get svc $SERVICE_NAME -o yaml -n $NAMESPACE | sed -e "s/$CURRENT_VERSION/$NEW_VERSION/g" | kubectl apply -n $NAMESPACE -f -

echo "[CLEANUP] Traffic is now pointing to the new version, cleaning up the old version..."
kubectl delete deploy $CURRENT_DEPLOYMENT_NAME -n $NAMESPACE

echo "[INFO] Done!"
