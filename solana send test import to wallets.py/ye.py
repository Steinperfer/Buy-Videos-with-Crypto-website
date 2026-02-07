from solathon.core.instructions import transfer
from solathon import Client, Transaction, PublicKey, Keypair

client = Client("https://api.devnet.solana.com")
sender = Keypair.from_private_key("you_private_key_here")
receiver = PublicKey("4fsYaTLhR2TAURyLjdrAY1pWNXp3Ww3F8K3KtNM7kcXH")
amount = 100  # get balacne from "python fetch_balance.py walletadress"

instruction = transfer(
        from_public_key=sender.public_key,
        to_public_key=receiver, 
        lamports=100
    )

transaction = Transaction(instructions=[instruction], signers=[sender])

result = client.send_transaction(transaction)
print("Transaction response: ", result)