# 04 - Setup, Git, Deploy

## Local Git (VS Code Terminal)

```powershell
cd "D:\Projects\2.0 Familytree"
git status
git add .
git commit -m "your message"
git push
```

## Remote

- Origin: `https://github.com/ananthsridev12/asf.git`

Check:

```powershell
git remote -v
git branch
git log --oneline -n 5
```

## HostGator / cPanel Deploy

## Recommended

- Keep code in GitHub
- Pull updates on server via cPanel Git Version Control

## Common cPanel Issues

- `could not read Username for https://github.com`
  - Use SSH clone URL + SSH deploy key in GitHub
- `Permission denied (publickey)`
  - Ensure server public key is added to GitHub account/repo deploy keys
- "repository has uncommitted changes"
  - Clean server working tree or redeploy to a fresh path

## cPanel Deploy Notes

- `.cpanel.yml` can automate post-pull copy/build steps
- If web root already has files, cPanel may refuse clone in same directory

## Safe Flow

1. Push from local
2. Pull on server repo
3. Verify permissions and config
4. Check `error_log` for runtime failures
