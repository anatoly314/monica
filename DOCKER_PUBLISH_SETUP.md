# Docker Publishing Setup Guide

This guide explains how to publish your Monica Docker images to Docker Hub and GitHub Container Registry.

## Prerequisites

1. A Docker Hub account (https://hub.docker.com)
2. A GitHub account with this repository

## Setup Steps

### 1. Create Docker Hub Access Token

1. Log in to Docker Hub (https://hub.docker.com)
2. Go to **Account Settings** → **Security** → **Access Tokens**
3. Click **New Access Token**
4. Give it a name like "GitHub Actions"
5. Set permissions to **Read, Write, Delete**
6. Copy the token (you won't be able to see it again!)

### 2. Add Secrets to GitHub Repository

1. Go to your GitHub repository: https://github.com/anatoly314/monica
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** and add these two secrets:

   **Secret 1:**
   - Name: `DOCKERHUB_USERNAME`
   - Value: `anatoly314` (your Docker Hub username)

   **Secret 2:**
   - Name: `DOCKERHUB_TOKEN`
   - Value: (paste the access token you created in step 1)

### 3. Update the Workflow File (Optional)

If you want to change the Docker image name, edit `.github/workflows/docker-publish.yml`:

```yaml
env:
  DOCKER_IMAGE: anatoly314/monica  # Change to your preferred name
```

### 4. Push the Workflow

```bash
git add .github/workflows/docker-publish.yml DOCKER_PUBLISH_SETUP.md
git commit -m "feat: add Docker Hub publishing workflow"
git push origin claude/fix-pattern-reminders-011CUu7ey26M5gxK8qpdbZf8
```

## How It Works

### Automatic Builds

The workflow will automatically build and push Docker images when:

- **Push to main branch** → `anatoly314/monica:main`, `anatoly314/monica:latest`
- **Push to 4.x branch** → `anatoly314/monica:4.x`
- **Push to feature branches** → `anatoly314/monica:<branch-name>`
- **Create version tag (v1.2.3)** → `anatoly314/monica:1.2.3`, `anatoly314/monica:1.2`, `anatoly314/monica:1`
- **Any push** → `anatoly314/monica:sha-<commit-hash>`

### Where Images Are Published

Images will be published to TWO registries:
1. **Docker Hub**: `docker pull anatoly314/monica:latest`
2. **GitHub Container Registry**: `docker pull ghcr.io/anatoly314/monica:latest`

### Multi-Architecture Support

Images are built for both:
- `linux/amd64` (Intel/AMD x86_64)
- `linux/arm64` (ARM 64-bit, like Apple M1/M2, Raspberry Pi)

## Usage

After the workflow runs, you can pull your image:

```bash
# From Docker Hub (public)
docker pull anatoly314/monica:latest

# From GitHub Container Registry (may require authentication)
docker pull ghcr.io/anatoly314/monica:latest
```

## Creating a Release

To create a versioned release:

```bash
# Tag your release
git tag v1.0.0
git push origin v1.0.0
```

This will create:
- `anatoly314/monica:v1.0.0`
- `anatoly314/monica:1.0.0`
- `anatoly314/monica:1.0`
- `anatoly314/monica:1`

## Monitoring Builds

1. Go to your repository on GitHub
2. Click the **Actions** tab
3. You'll see the "Docker Publish" workflow running
4. Click on a run to see detailed logs

## Testing Locally

Before pushing, you can test the Docker build locally:

```bash
# Build the image
docker build -f scripts/docker/Dockerfile -t anatoly314/monica:test .

# Run it
docker run -p 8080:80 anatoly314/monica:test
```

## Troubleshooting

### Build Fails with "unauthorized"
- Check that your `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` secrets are set correctly
- Make sure the token has Read, Write, Delete permissions

### Image not appearing on Docker Hub
- Verify the repository `anatoly314/monica` exists on Docker Hub (it will be created automatically on first push)
- Check the Actions tab on GitHub for any errors

### Build takes too long
- The workflow includes caching for composer and yarn dependencies
- First build will be slow, subsequent builds will be faster

## Optional: Make Docker Hub Repository Public

After the first push:
1. Go to https://hub.docker.com/r/anatoly314/monica
2. Click **Settings**
3. Under **Visibility**, select **Public**
4. Click **Save**

Now anyone can pull your image without authentication!
