import requests

def fetch_solana_price():
    url = "https://api.coinbase.com/v2/prices/SOL-USD/spot"
    response = requests.get(url, timeout=10)

    if response.status_code == 200:
        data = response.json()
        return f"${data['data']['amount']}"
    else:
        return "Price not available"

def hello_world():
    return "Hello, World!"

if __name__ == "__main__":
    price = fetch_solana_price()
    print(f"Price: {price}")