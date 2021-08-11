<?php
require_once("config.php");

if(!isset($_SESSION["userLoggedIn"])) {
    header("Location: /chat/login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="main-chat-container">
        <div class="chat-container">
            <div class="chat-header">
                <span id="otherUserName">Welcome To Chat</span>
            </div>
            <div id="chatArea" class="chat">
                Please Select A User To Chat With
            </div>
            <div class="input-container">
                <textarea id="chatMessage" name="chatMessage" value="" rows="3" cols="50" placeholder="Type your message here"></textarea>
                <button class="chat-submit" onclick="sendMessage()">Send</button>
            </div>
        </div>
        <div id="users" class="users-container">
            <span class="users-header">Online Users</span>
        </div>
    </div>
</body>
</html>

<script>
    let currentConnectionId, currentUserId, toConnectionId, toUserId, userSelected;
    const conn = new WebSocket('ws://localhost:8080?token=<?php echo $_SESSION["chatToken"].'&email='.$_SESSION["userLoggedIn"]?>');
    conn.onopen = function(e) {
        console.log("Connection established!");
    };

    conn.onmessage = function(e) {
        const data = JSON.parse(e.data);
        switch(data.type) {
            case "CONNECTION_ESTABLISHED":
                currentConnectionId = data.connId;
                currentUserId = data.userId;
                const usersContainer = document.getElementById("users");
                fetch(`/chat/ajax/getOnlineUsers.php`).then(response => response.json()).then(res => {
                    if(res.length === 0) return;
                    res.forEach(user => {
                        console.log(user);
                        if(user.id === currentUserId) return;
                        users.insertAdjacentHTML("beforeend", `
                            <span id="${user.id}" class="user" onclick="initChat(${user.id}, ${user.conn_id},'${user.name}')">${user.name}<span id="msgCount" class="msg-count hide"></span></span>
                        `);
                    });
                }).catch(err => console.log(err));
                        break;
            case "NEW_USER_CONNECTED": 
                document.getElementById("users").insertAdjacentHTML("beforeend", `<span id=${data.userId} class="user" onclick="initChat(${data.userId}, ${data.connId}, '${data.name}')">${data.name}<span id="msgCount" class="msg-count hide"></span></span>`);
                break;
            case "USER_DISCONNECTED": 
                const spanToRemove = document.getElementById(data.userId);
                spanToRemove.parentNode.removeChild(spanToRemove);
                break;
            case "NEW_MESSAGE": 
                if(data.fromUserId !== toUserId && data.fromUserId !== currentUserId) {
                    const fromUserSpan = document.getElementById(data.fromUserId);
                    const fromUserSpanCount = fromUserSpan.querySelector(".msg-count");
                    if(fromUserSpanCount.innerHTML === "" || parseInt(fromUserSpanCount.innerHTML) === 0) {
                        fromUserSpanCount.innerHTML = "1";
                        fromUserSpanCount.classList.remove("hide");
                    }
                    else {
                        let count = parseInt(fromUserSpanCount.innerHTML);
                        count++
                        fromUserSpanCount.innerHTML = count;
                    }
                }
                if(currentUserId == data.toUserId && toUserId == data.fromUserId || currentUserId == data.fromUserId && toUserId == data.toUserId) {
                    //currently chatting with this user
                    chatArea.insertAdjacentHTML("beforeend", `
                        <div class="msg-container ${data.fromUserId === currentUserId ? 'blue-container' : 'dark-container'}">
                            <div class="msg">${data.body}</div>
                        </div>
                    `);
                    readChat();
                }
                break;
        }
    };

    const initChat = (userId, connId, name) => {
        userSelected = true;
        toConnectionId = connId;
        toUserId = userId;
        const otherUserName = document.getElementById("otherUserName");
        const chatArea = document.getElementById("chatArea");
        chatArea.innerHTML = "";
        otherUserName.innerHTML = name;

        fetch(`/chat/ajax/getOlderMessages.php?to=${userId}&from=${currentUserId}`).then(response => response.json()).then(res => {
            if(res.length === 0) return;
            res.forEach(msg => {
                chatArea.insertAdjacentHTML("beforeend", `
                    <div class="msg-container ${msg.from_id === currentUserId ? 'blue-container' : 'dark-container'}">
                        <div class="msg">${msg.body}</div>
                    </div>
                `);
            });
            readChat();
        }).catch(err => console.log(err));
    }

    const sendMessage = () => {
        const msgInput = document.getElementById("chatMessage");
        const msg = msgInput.value;
        if(msg === "" || !userSelected) return; 
        conn.send(JSON.stringify({
            toConnectionId,
            toUserId, 
            fromUserId: currentUserId,
            body: msg
        }));
        msgInput.value = "";
        readChat();
    }

    const readChat = () => {
        const chatArea = document.getElementById("chatArea");
        chatArea.scrollTop = chatArea.scrollHeight;

        const fromUserSpan = document.getElementById(toUserId);
        const fromUserSpanCount = fromUserSpan.querySelector(".msg-count");
        fromUserSpanCount.innerHTML = "";
        fromUserSpanCount.classList.add("hide");
    }

</script>