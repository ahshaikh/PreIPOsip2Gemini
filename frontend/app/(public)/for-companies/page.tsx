'use client';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import Link from 'next/link';
import {
  Building2,
  Users,
  TrendingUp,
  Shield,
  Zap,
  Globe,
  Target,
  CheckCircle,
  ArrowRight,
  BarChart3,
  Rocket,
  DollarSign,
  Award,
  Sparkles,
  Clock,
  FileText,
  MessageSquare,
  Star
} from 'lucide-react';

export default function ForCompaniesPage() {
  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white py-20 lg:py-32">
        <div className="absolute inset-0 bg-[url('/grid.svg')] opacity-10"></div>
        <div className="container mx-auto px-4 relative z-10">
          <div className="max-w-4xl mx-auto text-center">
            <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
              <Sparkles className="h-4 w-4" />
              <span className="text-sm font-medium">India's Leading Pre-IPO Investment Platform</span>
            </div>
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
              Showcase Your Company to
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                20,000+ Active Investors
              </span>
            </h1>
            <p className="text-xl md:text-2xl mb-8 text-blue-100 leading-relaxed">
              List your Pre-IPO shares on India's most trusted platform.
              <strong className="text-white"> Absolutely FREE.</strong> No hidden charges. Ever.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link href="/company/register">
                <Button size="lg" className="bg-white text-blue-600 hover:bg-blue-50 text-lg px-8 py-6 h-auto">
                  Register Your Company Free
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <Link href="/company/login">
                <Button size="lg" variant="outline" className="border-2 border-white text-white hover:bg-white/10 text-lg px-8 py-6 h-auto">
                  Company Login
                </Button>
              </Link>
            </div>
            <p className="mt-6 text-sm text-blue-200">
              ✓ No listing fees  ✓ No commission  ✓ No hidden costs  ✓ SEBI compliant platform
            </p>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-12 bg-gray-50 dark:bg-gray-900 border-y">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {[
              { number: '20,000+', label: 'Active Investors', icon: Users },
              { number: '₹500Cr+', label: 'Funds Deployed', icon: DollarSign },
              { number: '150+', label: 'Companies Listed', icon: Building2 },
              { number: '4.8/5', label: 'Trust Rating', icon: Star },
            ].map((stat, idx) => (
              <div key={idx} className="text-center">
                <stat.icon className="h-8 w-8 mx-auto mb-3 text-blue-600" />
                <div className="text-3xl font-bold text-gray-900 dark:text-white mb-1">{stat.number}</div>
                <div className="text-sm text-gray-600 dark:text-gray-400">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Why Choose Us Section */}
      <section className="py-20 bg-white dark:bg-gray-950">
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              Why Companies Choose PreIPOsip
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400">
              The most transparent, efficient, and investor-friendly platform for Pre-IPO fundraising
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              {
                icon: DollarSign,
                title: '100% Free Listing',
                description: 'Zero listing fees, zero commission, zero hidden charges. We believe great companies should be accessible to all investors.',
                color: 'text-green-600 bg-green-50 dark:bg-green-950'
              },
              {
                icon: Users,
                title: 'Access to 20,000+ Investors',
                description: 'Connect with our verified investor community actively seeking Pre-IPO opportunities across sectors.',
                color: 'text-blue-600 bg-blue-50 dark:bg-blue-950'
              },
              {
                icon: Shield,
                title: 'SEBI Compliant Platform',
                description: 'Fully compliant with regulatory frameworks. Your company data is secure with bank-grade encryption.',
                color: 'text-purple-600 bg-purple-50 dark:bg-purple-950'
              },
              {
                icon: Zap,
                title: 'Quick Setup - Go Live in 24 Hours',
                description: 'Simple onboarding process. Our team helps you create an attractive company profile and listing.',
                color: 'text-orange-600 bg-orange-50 dark:bg-orange-950'
              },
              {
                icon: BarChart3,
                title: 'Real-time Analytics Dashboard',
                description: 'Track investor interest, profile views, investment inquiries, and engagement metrics in real-time.',
                color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950'
              },
              {
                icon: Target,
                title: 'Targeted Investor Matching',
                description: 'Our AI matches your company with investors interested in your sector, stage, and ticket size.',
                color: 'text-pink-600 bg-pink-50 dark:bg-pink-950'
              },
              {
                icon: Globe,
                title: 'Pan-India Reach',
                description: 'Access investors from metros to tier-2 cities. Expand your investor base beyond traditional networks.',
                color: 'text-teal-600 bg-teal-50 dark:bg-teal-950'
              },
              {
                icon: MessageSquare,
                title: 'Direct Investor Communication',
                description: 'Engage directly with potential investors through our secure messaging and Q&A system.',
                color: 'text-yellow-600 bg-yellow-50 dark:bg-yellow-950'
              },
              {
                icon: Award,
                title: 'Brand Visibility & Credibility',
                description: 'Featured placement opportunities, press releases, and investor webinars to boost your visibility.',
                color: 'text-red-600 bg-red-50 dark:bg-red-950'
              },
            ].map((feature, idx) => (
              <Card key={idx} className="border-2 hover:border-blue-500 hover:shadow-lg transition-all duration-300">
                <CardContent className="p-6">
                  <div className={`${feature.color} w-14 h-14 rounded-lg flex items-center justify-center mb-4`}>
                    <feature.icon className="h-7 w-7" />
                  </div>
                  <h3 className="text-xl font-semibold mb-3">{feature.title}</h3>
                  <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {feature.description}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works Section */}
      <section className="py-20 bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-blue-950">
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              List Your Company in 3 Simple Steps
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400">
              Get your Pre-IPO shares in front of thousands of investors within 24 hours
            </p>
          </div>

          <div className="max-w-5xl mx-auto">
            <div className="space-y-12">
              {[
                {
                  step: '1',
                  title: 'Create Your Company Profile',
                  description: 'Register your company with basic details - CIN, sector, contact information. Upload essential documents like incorporation certificate and pitch deck.',
                  time: '10 minutes',
                  icon: FileText
                },
                {
                  step: '2',
                  title: 'Complete Due Diligence & Approval',
                  description: 'Our team reviews your submission for compliance and authenticity. We help optimize your listing for maximum investor interest.',
                  time: '24-48 hours',
                  icon: Shield
                },
                {
                  step: '3',
                  title: 'Go Live & Connect with Investors',
                  description: 'Your company goes live on the platform. Start receiving investor inquiries, track analytics, and manage communications through your dashboard.',
                  time: 'Instant',
                  icon: Rocket
                },
              ].map((step, idx) => (
                <div key={idx} className="relative flex items-start gap-6 bg-white dark:bg-gray-800 p-8 rounded-xl shadow-md hover:shadow-xl transition-all">
                  <div className="flex-shrink-0">
                    <div className="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-full flex items-center justify-center text-2xl font-bold">
                      {step.step}
                    </div>
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-3">
                      <step.icon className="h-6 w-6 text-blue-600" />
                      <h3 className="text-2xl font-semibold">{step.title}</h3>
                    </div>
                    <p className="text-gray-600 dark:text-gray-400 mb-3 leading-relaxed">
                      {step.description}
                    </p>
                    <div className="flex items-center gap-2 text-sm text-blue-600 font-medium">
                      <Clock className="h-4 w-4" />
                      <span>{step.time}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="text-center mt-12">
            <Link href="/company/register">
              <Button size="lg" className="bg-blue-600 hover:bg-blue-700 text-lg px-10 py-6 h-auto">
                Start Your Free Listing Now
                <ArrowRight className="ml-2 h-5 w-5" />
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* Benefits Section */}
      <section className="py-20 bg-white dark:bg-gray-950">
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              What You Get with PreIPOsip
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400">
              Comprehensive tools and support to make your Pre-IPO offering successful
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
            {[
              'Dedicated company profile page with rich media support',
              'Real-time investor interest tracking and analytics',
              'Direct messaging system with potential investors',
              'Featured placement opportunities on homepage',
              'Email marketing to our investor database',
              'Webinar hosting for investor presentations',
              'Document repository for due diligence',
              'Secure data room for confidential information',
              'Transaction facilitation and escrow services',
              'Investor verification and KYC management',
              'Post-listing support and investor relations',
              'Regular performance reports and insights',
            ].map((benefit, idx) => (
              <div key={idx} className="flex items-start gap-3 p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                <CheckCircle className="h-6 w-6 text-green-600 flex-shrink-0 mt-0.5" />
                <span className="text-gray-700 dark:text-gray-300 font-medium">{benefit}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
        <div className="container mx-auto px-4">
          <div className="max-w-4xl mx-auto text-center">
            <h2 className="text-3xl md:text-5xl font-bold mb-6">
              Ready to Connect with 20,000+ Investors?
            </h2>
            <p className="text-xl mb-8 text-blue-100">
              Join 150+ companies already raising capital on PreIPOsip.
              Registration is 100% free with no hidden costs.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center mb-8">
              <Link href="/company/register">
                <Button size="lg" className="bg-white text-blue-600 hover:bg-blue-50 text-lg px-10 py-6 h-auto">
                  Register Your Company Free
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <Link href="/contact">
                <Button size="lg" variant="outline" className="border-2 border-white text-white hover:bg-white/10 text-lg px-10 py-6 h-auto">
                  Talk to Our Team
                </Button>
              </Link>
            </div>
            <p className="text-sm text-blue-200">
              Questions? Email us at <a href="mailto:companies@preiposip.com" className="underline font-medium">companies@preiposip.com</a>
            </p>
          </div>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="py-20 bg-gray-50 dark:bg-gray-900">
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto">
            <h2 className="text-3xl md:text-4xl font-bold mb-12 text-center">
              Frequently Asked Questions
            </h2>
            <div className="space-y-6">
              {[
                {
                  q: 'Is there really no cost to list my company?',
                  a: 'Yes, listing your company on PreIPOsip is 100% free. We don\'t charge any listing fees, subscription fees, or commissions. Our revenue model is based on value-added services that are completely optional.'
                },
                {
                  q: 'What documents do I need to register?',
                  a: 'You\'ll need your Certificate of Incorporation, latest financial statements, pitch deck, and details of your Pre-IPO share offering. Our team will guide you through the process.'
                },
                {
                  q: 'How long does the approval process take?',
                  a: 'Typically 24-48 hours. Our team reviews your submission for compliance and authenticity. If any additional information is needed, we\'ll reach out immediately.'
                },
                {
                  q: 'Can I control who sees my information?',
                  a: 'Absolutely. You have full control over what information is public vs. private. Sensitive documents can be shared only with verified, interested investors through our secure data room.'
                },
                {
                  q: 'Do you help with transaction facilitation?',
                  a: 'Yes! We provide end-to-end transaction support including legal documentation, escrow services, and post-transaction compliance. These are optional premium services.'
                },
                {
                  q: 'What types of companies can list?',
                  a: 'We work with private limited companies across all sectors planning Pre-IPO fundraising. Your company should be registered in India and comply with SEBI regulations for share transfers.'
                },
              ].map((faq, idx) => (
                <Card key={idx} className="border-l-4 border-l-blue-600">
                  <CardContent className="p-6">
                    <h3 className="text-lg font-semibold mb-3 text-gray-900 dark:text-white">
                      {faq.q}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                      {faq.a}
                    </p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
