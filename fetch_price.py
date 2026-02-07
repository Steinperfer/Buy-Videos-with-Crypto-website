import requests
from bs4 import BeautifulSoup
import re

def fetch_solana_price(query):
    search_url = f"https://www.bing.com/search?q={query}"
    response = requests.get(search_url)
    
    if response.status_code == 200:
        soup = BeautifulSoup(response.text, 'html.parser')
        price_div = soup.find('div', class_='df_da')
        if price_div:
            price_text = price_div.find('div', class_='b_focusTextMedium')
            if price_text:
                return price_text.text.strip()
        
        # Fallback to regex search
        page_text = soup.get_text()
        price_matches = re.findall(r'\$[0-9]+\.[0-9]{2}', page_text)
        if price_matches:
            return price_matches[0]
    
    return "Price not available"

def hello_world():
    return "Hello, World!"

if __name__ == '__main__':
    price = fetch_solana_price('coinbase solana price')
    print(f"Price: {price}")
