#!/bin/bash

# Prepare Rainlo for Render Deployment
echo "🚀 Preparing Rainlo for Render deployment..."

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo "❌ Not in a git repository. Please run this from your project root."
    exit 1
fi

# Check if required files exist
echo "📋 Checking required files..."

required_files=("Dockerfile" "render.yaml" "composer.json" "artisan")
missing_files=()

for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -ne 0 ]; then
    echo "❌ Missing required files: ${missing_files[*]}"
    exit 1
fi

echo "✅ All required files present"

# Check git status
echo "📊 Checking git status..."
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  You have uncommitted changes:"
    git status --short
    echo ""
    read -p "Do you want to commit these changes? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "📝 Committing changes..."
        git add .
        git commit -m "Prepare for Render deployment"
    else
        echo "⚠️  Proceeding with uncommitted changes..."
    fi
fi

# Check if we're on master branch
current_branch=$(git branch --show-current)
if [ "$current_branch" != "master" ]; then
    echo "⚠️  You're on branch '$current_branch', but Render will deploy from 'master'"
    read -p "Do you want to switch to master branch? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git checkout master
        git merge "$current_branch"
    fi
fi

# Push to GitHub
echo "📤 Pushing to GitHub..."
git push origin master

echo ""
echo "🎉 Ready for Render deployment!"
echo ""
echo "📋 Next steps:"
echo "1. Go to render.com and sign up/login"
echo "2. Click 'New +' → 'Blueprint'"
echo "3. Connect your GitHub repository"
echo "4. Render will detect render.yaml and create services automatically"
echo "5. Wait for deployment to complete (5-10 minutes)"
echo ""
echo "📖 For detailed instructions, see: RENDER_DEPLOYMENT.md"
echo ""
echo "🌐 Your API will be available at:"
echo "   https://rainlo-api.onrender.com"
echo ""
echo "✅ All done! Happy deploying! 🚀"
