# Docker Publishing Setup Guide

This project publishes Docker images to **TWO** registries using **separate workflows**:

1. **GitHub Container Registry (GHCR)** - `ghcr.io/anatoly314/monica` (automatic, no setup needed)
2. **Docker Hub** - `anatoly314/monica` (requires Docker Hub account setup)

---

## 📦 Workflow 1: GitHub Container Registry (GHCR)

**File:** `.github/workflows/docker-ghcr.yml`

### ✅ Already Set Up - No Action Needed!

This workflow uses the built-in `GITHUB_TOKEN` and requires **zero configuration**.

**Images published to:**
```
ghcr.io/anatoly314/monica:latest
ghcr.io/anatoly314/monica:main
ghcr.io/anatoly314/monica:4.x
```

### Making GHCR Package Public

After the first build, make your package public:

1. Go to https://github.com/anatoly314/monica/pkgs/container/monica
2. Click **Package settings**
3. Scroll to **Danger Zone** → **Change visibility**
4. Select **Public** → Confirm

Then anyone can pull:
```bash
docker pull ghcr.io/anatoly314/monica:latest
```

---

## 🐳 Workflow 2: Docker Hub

**File:** `.github/workflows/docker-hub.yml`

### Setup Required

**Images will be published to:**
```
docker.io/anatoly314/monica:latest
docker.io/anatoly314/monica:main
docker.io/anatoly314/monica:4.x
```

### Step 1: Create Docker Hub Access Token

1. Log in to https://hub.docker.com
2. Go to **Account Settings** → **Security** → **Access Tokens**
3. Click **New Access Token**
4. Name: "GitHub Actions Monica"
5. Permissions: **Read, Write, Delete**
6. Click **Generate** and **copy the token** (you won't see it again!)

### Step 2: Add Secrets to GitHub

1. Go to https://github.com/anatoly314/monica/settings/secrets/actions
2. Click **New repository secret** for each:

**Secret 1:**
- Name: `DOCKERHUB_USERNAME`
- Value: `anatoly314`

**Secret 2:**
- Name: `DOCKERHUB_TOKEN`
- Value: (paste the token from Step 1)

### Step 3: Make Docker Hub Repository Public (Optional)

After the first push:
1. Go to https://hub.docker.com/r/anatoly314/monica
2. **Settings** → **Visibility** → **Public**
3. **Save**

---

## 🚀 How the Workflows Trigger

### GHCR Workflow (`.github/workflows/docker-ghcr.yml`)
Triggers on push to:
- `main` branch
- `4.x` branch
- `4.x-pattern-reminders-fix` branch
- Version tags (`v*`)

### Docker Hub Workflow (`.github/workflows/docker-hub.yml`)
Triggers on push to:
- `main` branch (only)
- `4.x` branch (only)
- Version tags (`v*`)
- Manual trigger via GitHub Actions UI

**Note:** Docker Hub workflow only runs on main branches and tags to save build minutes.

---

## 📥 Using Published Images

### From GHCR (Recommended for private/testing)
```bash
docker pull ghcr.io/anatoly314/monica:latest
docker run -p 8080:80 ghcr.io/anatoly314/monica:latest
```

### From Docker Hub (Recommended for public distribution)
```bash
docker pull anatoly314/monica:latest
docker run -p 8080:80 anatoly314/monica:latest
```

Both images are **identical** - use whichever registry you prefer!

---

## 🏷️ Image Tags

Both workflows create the same tags:

| Trigger | Tags Created |
|---------|-------------|
| Push to `main` | `latest`, `main`, `sha-abc1234` |
| Push to `4.x` | `4.x`, `sha-abc1234` |
| Push tag `v1.2.3` | `v1.2.3`, `1.2.3`, `1.2`, `1`, `sha-abc1234` |
| Push to feature branch | `<branch-name>`, `sha-abc1234` (GHCR only) |

---

## 🔍 Monitoring Builds

1. Go to https://github.com/anatoly314/monica/actions
2. You'll see TWO workflows:
   - **Docker GHCR Publish** (always runs)
   - **Docker Hub Publish** (runs if secrets are configured)
3. Click on any run to see logs

---

## 📋 Creating Releases

To publish a versioned release to both registries:

```bash
# Tag the release
git tag v1.0.0
git push origin v1.0.0
```

This creates:
- **GHCR:** `ghcr.io/anatoly314/monica:v1.0.0`, `:1.0.0`, `:1.0`, `:1`
- **Docker Hub:** `anatoly314/monica:v1.0.0`, `:1.0.0`, `:1.0`, `:1`

---

## 🧪 Testing Locally

Build and test before pushing:

```bash
# Build
docker build -f scripts/docker/Dockerfile -t monica-test .

# Run
docker run -p 8080:80 monica-test

# Test
open http://localhost:8080
```

---

## 📖 Example docker-compose.yml

Users can choose either registry:

```yaml
version: '3.8'

services:
  monica:
    # Choose one:
    image: anatoly314/monica:latest           # Docker Hub
    # image: ghcr.io/anatoly314/monica:latest # GHCR

    ports:
      - "8080:80"
    environment:
      - APP_KEY=base64:your-app-key-here
      - DB_HOST=mysql
      - DB_DATABASE=monica
      - DB_USERNAME=monica
      - DB_PASSWORD=secret
    depends_on:
      - mysql

  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_DATABASE=monica
      - MYSQL_USER=monica
      - MYSQL_PASSWORD=secret
      - MYSQL_ROOT_PASSWORD=root
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

---

## 🎯 What Happens After Merge

When you merge `claude/fix-pattern-reminders-011CUu7ey26M5gxK8qpdbZf8` to `main`:

1. **GHCR Workflow:** Builds automatically (no setup needed)
   - Image: `ghcr.io/anatoly314/monica:latest`

2. **Docker Hub Workflow:**
   - If secrets configured: Builds and publishes to `anatoly314/monica:latest`
   - If secrets NOT configured: Skips (no error)

---

## ❓ Troubleshooting

### GHCR: "permission denied"
- Shouldn't happen (uses automatic `GITHUB_TOKEN`)
- Check workflow has `permissions: packages: write` ✅

### Docker Hub: "unauthorized"
- Check `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` secrets are set
- Verify token has Read, Write, Delete permissions
- Make sure token hasn't expired

### Docker Hub workflow skipped
- Normal if secrets aren't configured yet
- Add the secrets to enable Docker Hub publishing

### Both workflows failing
- Check Actions tab for specific error messages
- Verify `scripts/docker/build.sh` exists and is executable

---

## 🎉 Summary

- **GHCR:** Works immediately, no setup required
- **Docker Hub:** Requires 2 secrets, then works automatically
- **Both:** Build identical multi-arch images
- **Choose:** Use GHCR for quick testing, Docker Hub for public distribution
