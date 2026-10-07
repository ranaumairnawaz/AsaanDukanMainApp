<x-mail::message>
# Welcome to Asaan Dukan, {{ $user->name }}! 🎉

Your new online store **{{ $tenant->name }}** has been successfully created. We are absolutely thrilled to have you onboard.

You are currently on a **7-Day Free Trial** of our platform. Take this time to explore all the premium features and set up your store perfectly.

<x-mail::button :url="config('app.url') . '/store/' . $tenant->store_slug">
Visit Your Storefront
</x-mail::button>

## Next Steps
- **Add Products**: Go to the Merchant Dashboard to list your items.
- **Customize Theme**: Adjust your colors and layout in Theme Settings.
- **Set Up Domain**: Request a custom subdomain or link your own domain.

If you have any questions or need help setting up, just reply to this email!

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
