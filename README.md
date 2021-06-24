# PageLocks Plugin

## Features:
- When lock is acquired, user is prevented from editing the page:
  - The form is "blocked" by adding an extra layer on top.
  - Editing buttons Move, Delete and Save are hidden
  - User can no longer toggle between Normal|Expert mode.
- Locks expire after `expiresAfter` seconds if no keepAlive has arrived during that period. 
  - E.g. when user moved out of Admin, or user went out for lunch...
  - User is notified when lock has been forcefully removed, or has expired.
- Keep alive request are being send every `keepAliveInterval` seconds.
- Using option `debug` all lock state changes can me logged
- Messages in client are translated
- Users are notified in banner in top of page
- Javascript is generated using strongly typed Typescript

## How does it work?

### Frontend
- Two javascript files are added to Admin panel
  - `pagelocker.js`: Injected into every Admin page
    - Sends async requests to acquire lock
    - Sends keepAlive requests to prevent lock from expiring
  - `pagelocksadmin.js`: Injected only into Admin page '/admin/locks', the page to list/remove locks
    - Lists all acquired locks for all pages/users
    - User can sends async request to remove lock for a certain route

### Backend
- All locks are kept in file `/data/pagelocks/locks.yaml`
- On each async request on the server, any expired lock is removed
- If route of page is like `/admin/pages(/pagename)+`:
  - Any existing lock of current user is removed
  - A new lock is acquired for user
- Else:
  - Any existing lock of current user is removed

## Quickstart
To get to know the plugin, use low settings for `keepAliveInterval` and `expiresAfter`. Also use `debug: true` to log all lock activity.

```
enabled: true
expiresAfter: 60            # Lock expires after x seconds of no keepAlive pings
keepAliveInterval: 30       # Send every x seconds a keepAlive ping to server
productionMode: false       # Add minified assets if true
debug: true                 # If true, write a log to data/pagelocks/debug.log
```

## Todo:
- More testing
