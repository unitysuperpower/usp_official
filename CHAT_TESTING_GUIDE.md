# Real-time Chat System - Testing & Troubleshooting Guide

## System Overview
The chat system uses Laravel Reverb (WebSocket server) for real-time bidirectional communication between users and admins.

## Prerequisites Checklist

### 1. Environment Configuration (.env)
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=950562
REVERB_APP_KEY=4yabfecd33vpwnxplmbz
REVERB_APP_SECRET=mgakudoxjynmcy8nz5k5
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 2. Required Services Running
- **Laravel Development Server**: `php artisan serve`
- **Reverb WebSocket Server**: `php artisan reverb:start --host=0.0.0.0 --port=8080`
- **Frontend Assets Built**: `npm run build` (or `npm run dev` for development)

## Testing Steps

### Step 1: Start All Services

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```
This should start on: http://127.0.0.1:8000

**Terminal 2 - Reverb WebSocket Server:**
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```
You should see: `INFO  Starting server on 0.0.0.0:8080 (localhost).`

**Terminal 3 - Build Assets (if not done):**
```bash
npm run build
```

### Step 2: Open Browser Developer Console
1. Open Chrome/Firefox/Edge
2. Press F12 to open Developer Tools
3. Go to **Console** tab
4. Keep this open during testing to see debug messages

### Step 3: Test as Regular User

1. **Login as regular user** (not admin)
2. **Navigate to**: http://127.0.0.1:8000/chat
3. **Check Console** for these messages:
   - `Echo initialized, subscribing to channel: chat.{conversationId}`
   - If Echo is not available, you'll see an error

4. **Send a test message**: "Hello from user"
5. **Check Console** for:
   - `Sending message: Hello from user`
   - `Request body: {conversation_id: X, message: "Hello from user"}`
   - `Response status: 200`
   - `Response data: {success: true, message: {...}}`
   - `Message sent successfully`

6. **Expected Result**: 
   - Message appears immediately in your chat window
   - Status shows "Waiting for agent..." (until admin responds)

### Step 4: Test as Admin

1. **Open new browser or incognito window**
2. **Login as admin user**
3. **Navigate to**: http://127.0.0.1:8000/admin/chat
4. **Click on the user's conversation**
5. **Check Console** for:
   - `Echo initialized, subscribing to channel: chat.{conversationId}`
   
6. **You should see**: User's message that was sent in Step 3

7. **Send admin reply**: "Hello, how can I help you?"
8. **Check Console** for:
   - `Admin sending message: Hello, how can I help you?`
   - `Response status: 200`
   - `Response data: {success: true, message: {...}}`
   - `Admin message sent successfully`

### Step 5: Verify Real-time Updates

1. **Go back to user's browser window**
2. **DO NOT refresh the page**
3. **Expected Result**: 
   - Admin's message appears automatically
   - You hear a notification sound
   - Status changes to "Chatting with [Admin Name]"
   - NO page refresh needed

4. **Send another message from user**
5. **Check admin window**: Message should appear instantly

## Troubleshooting

### Problem: "Waiting for agent..." persists
**Possible Causes:**
1. No admin has replied yet (this is normal behavior)
2. Admin replied but message didn't save to database

**Solution:**
- Check database: `SELECT * FROM chat_messages WHERE conversation_id = X ORDER BY created_at DESC`
- Verify admin response was actually saved

### Problem: Messages not sending
**Console Error**: "Failed to send message"

**Check:**
1. **CSRF Token**: Open Console, type `document.querySelector('input[name="_token"]').value`
   - Should return a long string
   - If null, form is missing @csrf directive

2. **Routes**: Run `php artisan route:list | grep chat`
   - Verify `POST /chat/send` exists (user)
   - Verify `POST /admin/chat/send` exists (admin)

3. **Database Connection**: 
   ```bash
   php artisan tinker
   >>> App\Models\ChatConversation::count()
   ```

4. **Permissions**: Check `storage/logs/laravel.log` for errors

### Problem: No real-time updates
**Symptoms**: Messages send successfully but don't appear until page refresh

**Check:**
1. **Reverb Server Running?**
   ```bash
   netstat -ano | findstr :8080
   ```
   Should show process listening on port 8080

2. **Echo Initialization**: In browser console, type:
   ```javascript
   window.Echo
   ```
   - Should return Echo object
   - If `undefined`, assets not loaded properly

3. **WebSocket Connection**: In Chrome DevTools:
   - Go to **Network** tab
   - Click **WS** (WebSockets) filter
   - Look for connection to `ws://localhost:8080/app/4yabfecd33vpwnxplmbz`
   - Status should be "101 Switching Protocols"

4. **Channel Authorization**: In Console, check for errors like:
   - `Authorization failed for channel`
   - This means routes/channels.php has incorrect logic

5. **Event Broadcasting**: Check Reverb terminal for activity:
   - When you send a message, you should see connection/broadcast activity

### Problem: Echo is undefined
**Causes:**
1. Assets not built: Run `npm run build`
2. Assets not loading: Check `public/build/manifest.json` exists
3. Vite config issue: Verify `@vite` directive in layout

**Solution:**
1. Clear caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

2. Rebuild assets:
   ```bash
   npm install
   npm run build
   ```

3. Hard refresh browser: Ctrl+F5

### Problem: 403 Forbidden on WebSocket
**Error**: "Authorization failed for private channel"

**Check routes/channels.php:**
```php
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\ChatConversation::find($conversationId);
    return $conversation && (
        $conversation->user_id === $user->id || 
        $user->is_admin
    );
});
```

**Verify:**
- User owns conversation OR is admin
- ChatConversation model exists with correct relationships

### Problem: Port 8080 already in use
**Error**: "Address already in use"

**Solution:**
1. Find process using port:
   ```bash
   netstat -ano | findstr :8080
   ```

2. Kill process (replace PID):
   ```bash
   taskkill /F /PID <PID>
   ```

3. Or use different port:
   ```bash
   php artisan reverb:start --port=8081
   ```
   (Update .env: `REVERB_PORT=8081`)

## Diagnostic Tools

### 1. Test WebSocket Connection
Visit: http://127.0.0.1:8000/chat/test
- Shows configuration
- Tests Echo availability
- Tests channel subscription
- Displays console output

### 2. Check Database State
```sql
-- View conversations
SELECT * FROM chat_conversations ORDER BY created_at DESC;

-- View messages for conversation ID 1
SELECT 
    m.*,
    u.name as user_name,
    u.is_admin
FROM chat_messages m
JOIN users u ON m.user_id = u.id
WHERE m.conversation_id = 1
ORDER BY m.created_at ASC;

-- Count unread messages
SELECT 
    conversation_id,
    COUNT(*) as unread_count
FROM chat_messages
WHERE is_admin = 0 AND is_read = 0
GROUP BY conversation_id;
```

### 3. Monitor Reverb Logs
In the Reverb terminal, you should see:
- `Connection established` when browsers connect
- Broadcast activity when messages sent
- `Connection closed` when browsers disconnect

### 4. Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```
Look for:
- Broadcasting errors
- Database errors
- Authorization failures

## Common Issues Summary

| Issue | Solution |
|-------|----------|
| Messages not sending | Check CSRF token, routes, database connection |
| No real-time updates | Verify Reverb running, Echo loaded, WebSocket connected |
| "Waiting for agent" persists | Normal until admin replies; check if admin message saved |
| Echo undefined | Build assets, clear caches, hard refresh |
| WebSocket 403 | Fix channel authorization in routes/channels.php |
| Port 8080 in use | Kill process or use different port |

## Success Indicators

When everything is working correctly:

✅ Reverb server shows active connections
✅ Console shows `Echo initialized, subscribing to channel`
✅ Network tab shows WebSocket connection (101 status)
✅ Messages send with status 200
✅ Messages appear instantly without page refresh
✅ Notification sound plays on new messages
✅ Admin can see user messages in real-time
✅ User can see admin responses in real-time

## Performance Tips

1. **Production**: Use queue workers for broadcasts
   ```bash
   php artisan queue:work
   ```

2. **SSL/TLS**: For production, use wss:// instead of ws://
   - Update `REVERB_SCHEME=https`
   - Configure SSL certificates

3. **Scaling**: Use Redis for Reverb backend
   - Install predis: `composer require predis/predis`
   - Configure in broadcasting.php

## Need More Help?

1. Enable debug mode: `APP_DEBUG=true` in .env
2. Check all logs: Laravel, Reverb, browser console
3. Test with diagnostic page: http://127.0.0.1:8000/chat/test
4. Verify all services running with `netstat` and process manager
