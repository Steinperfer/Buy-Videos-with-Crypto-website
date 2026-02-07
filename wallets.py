import base58
from solathon import Client
from solders.keypair import Keypair

# Initialize Solana client (adjust if using mainnet or other networks)
client = Client("https://api.devnet.solana.com")

def generate_wallet():
    # Generate a new Solana Keypair
    account = Keypair()
    public_key = str(account.pubkey())
    
    # Print the generated public key
    print(public_key)
    return public_key

if __name__ == "__main__":
    # Generate a single wallet address
    generate_wallet()
