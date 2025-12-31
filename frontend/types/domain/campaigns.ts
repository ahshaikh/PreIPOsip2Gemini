export interface CampaignOffer {
    id: string;
    code: string;
    title: string;
    description: string;
    expiry: string | null;
    type: 'discount' | 'bonus';
}