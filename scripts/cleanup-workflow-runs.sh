#!/bin/bash
# Script to clean up GitHub Actions workflow runs
# Keeps only the latest successful run for each workflow

set -e

# CI/CD Pipeline workflow
echo "Cleaning up CI/CD Pipeline workflow runs..."
LATEST_SUCCESSFUL_CICD=$(gh run list --workflow "CI/CD Pipeline" --json databaseId,status --jq '.[] | select(.status=="completed") | .databaseId' | head -n 1)
echo "Keeping latest successful run: $LATEST_SUCCESSFUL_CICD"

# Get all runs except the latest successful one
OLD_CICD_RUNS=$(gh run list --workflow "CI/CD Pipeline" --json databaseId --jq '.[] | .databaseId' | grep -v "$LATEST_SUCCESSFUL_CICD" || true)

# Delete old runs
for run_id in $OLD_CICD_RUNS; do
  echo "Deleting CI/CD run: $run_id"
  gh run delete "$run_id" || echo "Failed to delete run $run_id"
done

# Terramate workflow
echo "Cleaning up Terramate workflow runs..."
LATEST_SUCCESSFUL_TERRAMATE=$(gh run list --workflow "Terramate" --json databaseId,status --jq '.[] | select(.status=="completed") | .databaseId' | head -n 1)
echo "Keeping latest successful run: $LATEST_SUCCESSFUL_TERRAMATE"

# Get all runs except the latest successful one
OLD_TERRAMATE_RUNS=$(gh run list --workflow "Terramate" --json databaseId --jq '.[] | .databaseId' | grep -v "$LATEST_SUCCESSFUL_TERRAMATE" || true)

# Delete old runs
for run_id in $OLD_TERRAMATE_RUNS; do
  echo "Deleting Terramate run: $run_id"
  gh run delete "$run_id" || echo "Failed to delete run $run_id"
done

echo "Cleanup complete!"
