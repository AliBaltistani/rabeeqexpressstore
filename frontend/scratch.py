import re

with open('src/pages/CheckoutPage.vue', 'r', encoding='utf-8') as f:
    content = f.read()

replacements = {
    'Total Order': '{{ $t(\'checkout.totalOrder\') }}',
    'Use Coupon?': '{{ $t(\'checkout.useCoupon\') }}',
    'Enter coupon code': '{{ $t(\'checkout.enterCouponCode\') }}',
    'Apply': '{{ $t(\'checkout.apply\') }}',
    'Order Details': '{{ $t(\'checkout.orderDetails\') }}',
    'Login / Register': '{{ $t(\'checkout.loginRegister\') }}',
    'Welcome, Dear Guest': '{{ $t(\'checkout.welcomeGuest\') }}',
    'Log In Or Create A New Account To Complete Your Order.': '{{ $t(\'checkout.loginSubtitle\') }}',
    'Please Add Your Contact Information': '{{ $t(\'checkout.guestSubtitle\') }}',
    'Purchase as guest ›': '{{ $t(\'checkout.purchaseAsGuest\') }}',
    'Email Address': '{{ $t(\'checkout.emailAddress\') }}',
    'Enter': '{{ $t(\'checkout.enter\') }}',
    'First Name': '{{ $t(\'checkout.firstName\') }}',
    'Last Name': '{{ $t(\'checkout.lastName\') }}',
    'Email': '{{ $t(\'checkout.email\') }}',
    'Phone Number': '{{ $t(\'checkout.phoneNumber\') }}',
    'Continue As Guest': '{{ $t(\'checkout.continueAsGuest\') }}',
    'Already have an account?': '{{ $t(\'checkout.alreadyHaveAccount\') }}',
    'Shipping Address': '{{ $t(\'checkout.shippingAddress\') }}',
    'Ensure The Delivery Address Is Accurate For Timely Delivery.': '{{ $t(\'checkout.ensureAddress\') }}',
    'Edit': '{{ $t(\'checkout.edit\') }}',
    'Country': '{{ $t(\'checkout.country\') }}',
    'Region': '{{ $t(\'checkout.region\') }}',
    'City': '{{ $t(\'checkout.city\') }}',
    'District': '{{ $t(\'checkout.district\') }}',
    'Street': '{{ $t(\'checkout.street\') }}',
    'Postal Code': '{{ $t(\'checkout.postalCode\') }}',
    'Building Number (Optional)': '{{ $t(\'checkout.buildingNo\') }}',
    'Building Description (Optional)': '{{ $t(\'checkout.buildingDesc\') }}',
    'Deliver order to someone else?': '{{ $t(\'checkout.deliverToOther\') }}',
    'Recipient\'s Name': '{{ $t(\'checkout.recipientName\') }}',
    'Get order updates via SMS': '{{ $t(\'checkout.smsUpdates\') }}',
    'Save': '{{ $t(\'checkout.save\') }}',
    'Shipping Company': '{{ $t(\'checkout.shippingCompany\') }}',
    'Select A Shipping Option That Works Best For You.': '{{ $t(\'checkout.selectShipping\') }}',
    'Confirm Shipping Company': '{{ $t(\'checkout.confirmShipping\') }}',
    'Additional Information And Preferences': '{{ $t(\'checkout.additionalInfo\') }}',
    'Confirm Information': '{{ $t(\'checkout.confirmInfo\') }}',
    'Payment': '{{ $t(\'checkout.payment\') }}',
    'Card Details': '{{ $t(\'checkout.cardDetails\') }}',
    'Card Holder Name': '{{ $t(\'checkout.cardHolderName\') }}',
    'Save my card details for future orders': '{{ $t(\'checkout.saveCard\') }}',
    'By making this payment, I acknowledge that I have read and agree to the terms and conditions of the site and acknowledge that I am 18 years old or over.': '{{ $t(\'checkout.agreeTerms\') }}',
    'Confirm Payment': '{{ $t(\'checkout.confirmPayment\') }}'
}

for k, v in replacements.items():
    content = content.replace(f'>{k}<', f'>{v}<')
    content = content.replace(f'placeholder="{k}"', f':placeholder="$t(\'checkout.\' + \'{k}\')"')
    content = content.replace(f'<span>{k}</span>', f'<span>{v}</span>')

content = content.replace('placeholder="Enter coupon code"', ':placeholder="$t(\'checkout.enterCouponCode\')"')
content = content.replace('placeholder="your@email.com"', ':placeholder="$t(\'checkout.emailAddress\')"')
content = content.replace('placeholder="Enter your first name"', ':placeholder="$t(\'checkout.firstName\')"')
content = content.replace('placeholder="Enter your last name"', ':placeholder="$t(\'checkout.lastName\')"')
content = content.replace('placeholder="example@mail.com"', ':placeholder="$t(\'checkout.email\')"')
content = content.replace('placeholder="Enter Name"', ':placeholder="$t(\'checkout.cardHolderName\')"')

with open('src/pages/CheckoutPage.vue', 'w', encoding='utf-8') as f:
    f.write(content)

print('Done')
