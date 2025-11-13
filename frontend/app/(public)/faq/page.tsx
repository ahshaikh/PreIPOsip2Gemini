// V-FINAL-1730-188
'use client';

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export default function FaqPage() {
  const { data: faqs, isLoading } = useQuery({
    queryKey: ['publicFaqs'],
    queryFn: async () => (await api.get('/public/faqs')).data,
  });

  if (isLoading) return <div className="container py-20">Loading FAQs...</div>;

  return (
    <div className="container py-20 max-w-3xl">
      <h1 className="text-4xl font-bold text-center mb-8">Frequently Asked Questions</h1>
      <Accordion type="single" collapsible className="w-full">
        {faqs?.map((faq: any) => (
          <AccordionItem key={faq.id} value={`item-${faq.id}`}>
            <AccordionTrigger>{faq.question}</AccordionTrigger>
            <AccordionContent>{faq.answer}</AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </div>
  );
}