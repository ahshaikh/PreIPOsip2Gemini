// V-PHASE4-1730-099
import { useQuery } from '@tanstack/react-query';
import api from './api';
import { Plan } from '@/types'; // We'll define this type

export const usePlans = () => {
  return useQuery<Plan[]>({
    queryKey: ['plans'],
    queryFn: async () => {
      const { data } = await api.get('/plans');
      return data;
    },
  });
};

export const usePageContent = (slug: string) => {
  return useQuery({
    queryKey: ['page', slug],
    queryFn: async () => {
      const { data } = await api.get(`/page/${slug}`);
      return data;
    },
    enabled: !!slug,
  });
};