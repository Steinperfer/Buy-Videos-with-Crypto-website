import sys
import requests
import json

def fetch_wallet_balance(wallet_address):
    balance_url = 'https://api.devnet.solana.com'
    balance_post_fields = json.dumps({
        'jsonrpc': '2.0',
        'id': 1,
        'method': 'getBalance',
        'params': [wallet_address]
    })
    headers = {'Content-Type': 'application/json'}
    
    try:
        response = requests.post(balance_url, headers=headers, data=balance_post_fields)
        response.raise_for_status()  # Raise an exception for HTTP errors
        balance_data = response.json()
        
        if 'result' in balance_data and 'value' in balance_data['result']:
            return balance_data['result']['value'] / 1e9  # Convert lamports to SOL
        
        return "Invalid response format"
    
    except requests.exceptions.RequestException as e:
        return f"Request failed: {e}"

if __name__ == '__main__':
    if len(sys.argv) > 1:
        wallet_address = sys.argv[1]
        balance = fetch_wallet_balance(wallet_address)
        print(f"{balance} SOL")
    else:
        print("No wallet address provided")
